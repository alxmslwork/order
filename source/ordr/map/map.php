<?php

function map_config() {
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

function map_initialize() {
}

function map_getconnection($login) {
    $config = map_config();
    foreach($config['shard'] as $k => $v) {
        $letter = substr($login, 0, strlen($k));
        if ($letter <= $k) {
            return $v;
        }
    }
    return false;
}

/**
 * Метод добалвения логина пользователя в карту идентификаторов
 * Атомарная операция
 * @param string $login логин пользователя
 * @return int|false результат добавления пользвоателя: идентификатор или FALSE
 */
function map_add($login) {
    $connection = map_getconnection($login);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_query('UPDATE ');
            mysql_close($link);
        }
    }
    return false;
}
