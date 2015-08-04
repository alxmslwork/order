<?php
/**
 * @return array настройки шардов пользователей
 */
function profile_config() {
    return [
        'shard' => [
            50000 => [
                'host'     => 'mysqluser1',
                'user'     => 'root',
                'password' => 'secret',
            ],
            100000 => [
                'host'     => 'mysqluser2',
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
                if (mysql_query('CREATE DATABASE IF NOT EXISTS user;', $link)) {
                    printf("%s: database user created\n", $v['host']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }

                mysql_select_db('user', $link);
                $createTableQuery = <<<EOD
CREATE TABLE IF NOT EXISTS user (
    user_id INT         NOT NULL,
    login   VARCHAR(5)  NOT NULL,
    hash    VARCHAR(72) NOT NULL,
    type    SMALLINT    NOT NULL,
    PRIMARY KEY (user_id)
);
EOD;
                if (mysql_query($createTableQuery, $link)) {
                    printf("%s: table user::user created\n", $v['host']);
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
function profile_add($userId, $login, $hash, $userType) {
    $connection = profile_getconnection($login);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db('user');
            if (mysql_query(sprintf('INSERT INTO user (user_id, login, hash, type) VALUES (%s, "%s", "%s", %s);'
                , $userId, $login, $hash, $userType), $link)) {

                if (mysql_affected_rows($link) == 1) {
                    return true;
                }
            }
        }
    }
    return false;
}
