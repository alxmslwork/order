<?php
/**
 * @return array настройки шардов карты логинов пользователя
 */
function authorizer_config() {
    return [
        'shard' => [
            'h' => [
                'host'     => 'mysqlmap1',
                'user'     => 'root',
                'password' => 'secret',
            ],
            'z' => [
                'host'     => 'mysqlmap2',
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
                if (mysql_query('CREATE DATABASE IF NOT EXISTS map;', $link)) {
                    printf("%s: database map created\n", $v['host']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }

                mysql_select_db('map', $link);
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
                    printf("%s: table map::map created\n", $v['host']);
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
            mysql_select_db('map');
            if (mysql_query(sprintf('INSERT INTO map (login, salt, hash) VALUES ("%s", "%s", "%s");'
                , $login, $salt, $hash)
                , $link)) {

                if (mysql_affected_rows($link) == 1) {
                    return $hash;
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
            mysql_select_db('map');
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