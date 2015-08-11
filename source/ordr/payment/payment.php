<?php

function payment_config() {
    return [
        'shard' => [
            1000 => [
                'db'       => 'payment1',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
            100000 => [
                'db'       => 'payment2',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
        ],
    ];
}

function payment_initialize() {
    if (PHP_SAPI == 'cli') {
        $config = payment_config();
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
CREATE TABLE IF NOT EXISTS payment (
    user_id INT           NOT NULL,
    value   DECIMAL(10,2) DEFAULT 0.0,
    updated INT           NOT NULL,
    PRIMARY KEY (user_id, updated)
);
EOD;
                if (mysql_query($createTableQuery, $link)) {
                    printf("%s: table %s::payment created\n", $v['host'], $v['db']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }
            } else {
                printf("%s: could not connect\n", $v['host']);
            }
        }
    } else {
        die('could not call payment_initialize from non-CLI mode');
    }
}

function payment_getconnection($userId) {
    $config = payment_config();
    foreach($config['shard'] as $k => $v) {
        if ($userId <= $k) {
            return $v;
        }
    }
    return false;
}

function payment_add($userId, $value) {
    $connection = payment_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('INSERT INTO payment (user_id, value, updated) VALUES (%s, %s, %s);'
                , $userId, $value, time()), $link)) {
                return mysql_affected_rows($link) == 1;
            }
        }
    }
    return false;
}
