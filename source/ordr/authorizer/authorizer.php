<?php
/**
 * @return array настройки шардов карты логинов пользователя
 */
function authorizer_config() {
    return [
        'shard' => [
            'h' => [
                'db'       => 'map1',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
            'z' => [
                'db'       => 'map2',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
        ],
    ];
}

/**
 * Фукнция инициализации БД карты логинов.
 * Испольузется только для CLI
 */
function authorizer_initialize() {
    if (PHP_SAPI == 'cli') {
        $config = authorizer_config();
        foreach($config['shard'] as $k => $v) {
            $link = mysql_connect($v['host'], $v['user'], $v['password']);
            if ($link) {
                if (mysql_query(sprintf('CREATE DATABASE IF NOT EXISTS %s;', $v['db']), $link)) {
                    printf("%s: database %s created\n", $v['host'], $v['db']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }

                mysql_select_db($v['db'], $link);
                $createTableQuery = <<<EOD
CREATE TABLE IF NOT EXISTS map (
    login   VARCHAR(5)  NOT NULL,
    salt    VARCHAR(32) NOT NULL,
    hash    VARCHAR(72) NOT NULL,
    user_id INT         DEFAULT NULL,
    PRIMARY KEY (login)
);
EOD;
                if (mysql_query($createTableQuery, $link)) {
                    printf("%s: table %s::map created\n", $v['host'], $v['db']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }
            } else {
                printf("%s: could not connect\n", $v['host']);
            }
        }
    } else {
        die('could not call map_initialize from non-CLI mode');
    }
}

/**
 * Функция получения настроек соединения для указанного логина
 * @param string $login интересующий логин
 * @return false|array массив настроек соединения или FALSE, если плохо всё
 */
function authorizer_getconnection($login) {
    $config = authorizer_config();
    foreach($config['shard'] as $k => $v) {
        $letter = substr($login, 0, strlen($k));
        if ($letter <= $k) {
            return $v;
        }
    }
    return false;
}

/**
 * Функция добавления логина пользователя в карту идентификаторов
 * @param string $login логин пользователя
 * @param string $password пароль пользователя
 * @return string|false результат добавления пользвоателя: хеш пароля или FALSE
 */
function authorizer_add($login, $password) {
    $connection = authorizer_getconnection($login);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            $salt = base64_encode(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
            $hash = password_hash($password, PASSWORD_BCRYPT, [
                'cost' => 11,
                'salt' => $salt,
            ]);
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('INSERT INTO map (login, salt, hash) VALUES ("%s", "%s", "%s");'
                , $login, $salt, $hash)
                , $link)) {

                if (mysql_affected_rows($link) == 1) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Функция обновления данных пользователя по результатам регистрации
 * @param string $login логин пользователя
 * @param int $userId идентификатор пользвоателя в системе
 * @return bool результат обнолвения данных пользователя
 */
function authorizer_update($login, $userId) {
    $connection = authorizer_getconnection($login);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('UPDATE map SET user_id = %s WHERE login = "%s" AND user_id IS NULL;', $userId, $login)
                , $link)) {
                if (mysql_affected_rows($link) == 1) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Функция проверки авторизации пользователя
 * @param string $login логин пользователя
 * @param string $password пароль пользователя
 * @return int идентификатор польвзоателя или код ошибки. Возможные коды ошибок:
 *      -1 - техническая проблема, надо повторить вход позже
 *      -2 - пользователь не найден, нужна регистрация
 *      -3 - пароль пользователя не совпадает
 */
function authorizer_check($login, $password) {
    $connection = authorizer_getconnection($login);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            $result = mysql_query(sprintf('SELECT * FROM map WHERE login = "%s" AND user_id IS NOT NULL;', $login)
                , $link);
            if ($result) {
                $row  = mysql_fetch_assoc($result);
                if ($row) {
                    $hash = password_hash($password, PASSWORD_BCRYPT, [
                        'cost' => 11,
                        'salt' => $row['salt'],
                    ]);
                    if ($row['hash'] === $hash) {
                        return $row['user_id'];
                    } else {
                        return -3;
                    }
                } else {
                    return -2;
                }
            } else {
                return -1;
            }
        }
    }
    return -1;
}
