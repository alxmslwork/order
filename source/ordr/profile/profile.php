<?php
/**
 * @return array настройки шардов пользователей
 */
function profile_config() {
    return [
        'shard' => [
            50000 => [
                'db'       => 'user1',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
            100000 => [
                'db'       => 'user2',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
        ],
    ];
}

/**
 * Фукнция инициализации БД пользователей
 * Испольузется только для CLI
 */
function profile_initialize() {
    if (PHP_SAPI == 'cli') {
        $config = profile_config();
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
CREATE TABLE IF NOT EXISTS user (
    user_id INT           NOT NULL,
    login   VARCHAR(5)    NOT NULL,
    type    SMALLINT      NOT NULL,
    money   DECIMAL(10,2) DEFAULT 0.0,
    PRIMARY KEY (user_id)
);
EOD;
                if (mysql_query($createTableQuery, $link)) {
                    printf("%s: table %s::user created\n", $v['host'], $v['db']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }
            } else {
                printf("%s: could not connect\n", $v['host']);
            }
        }
    } else {
        die('could not call user_initialize from non-CLI mode');
    }
}

/**
 * Функция получения настроек соединения для данных пользователя
 * @param int $userId идентификатор пользователя
 * @return false|array массив настроек соединения или FALSE, если плохо всё
 */
function profile_getconnection($userId) {
    $config = profile_config();
    foreach($config['shard'] as $k => $v) {
        if ($userId <= $k) {
            return $v;
        }
    }
    return false;
}

/**
 * Функция добавления данных пользвоателя
 * @param int $userId идентификатор пользователя
 * @param string $login логин пользователя
 * @param int $userType тип пользователя
 * @return bool результат сохрания данных пользователя
 */
function profile_add($userId, $login, $userType) {
    $connection = profile_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('INSERT INTO user (user_id, login, type) VALUES (%s, "%s", %s);'
                , $userId, $login, $userType), $link)) {

                if (mysql_affected_rows($link) == 1) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Функция получения профиля пользователя
 * @param int $userId идентификатор пользователя
 * @return array|false данные профиля пользователя или FALSE, если что-то плохо
 */
function profile_get($userId) {
    $connection = profile_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            $result = mysql_query(sprintf('SELECT * FROM user WHERE user_id = %s;', $userId), $link);
            if ($result) {
                return mysql_fetch_assoc($result);
            }
        }
    }
    return false;
}

function profile_update($userId, $money) {
    $connection = profile_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('UPDATE user SET money = money + %s WHERE user_id = %s;', $money, $userId), $link)) {
                return mysql_affected_rows($link) == 1;
            }
        }
    }
    return false;
}
