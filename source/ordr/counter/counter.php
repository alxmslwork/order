<?php
/**
 * @return array конфигурационные данные счетчика
 */
function counter_config() {
    return [
        'host'     => 'mysqlcommon',
        'user'     => 'root',
        'password' => 'secret',
    ];
}

/**
 * Фукнция инициализации БД счетчика.
 * Испольузется только для CLI
 */
function counter_initialize() {
    if (PHP_SAPI == 'cli') {
        $config = counter_config();
        $link = mysql_connect($config['host'], $config['user'], $config['password']);
        if ($link) {
            if (mysql_query('CREATE DATABASE IF NOT EXISTS counter;', $link)) {
                printf("database counter created\n");
            } else {
                die(sprintf("%s\n", mysql_error()));
            }

            mysql_select_db('counter', $link);
            $createTableQuery = <<<EOD
CREATE TABLE IF NOT EXISTS counter (
    name  VARCHAR(10) NOT NULL,
    value INT         DEFAULT 0 NOT NULL,
    PRIMARY KEY (name)
);
EOD;
            if (mysql_query($createTableQuery, $link)) {
                printf("table counter::counter created\n");
            } else {
                die(sprintf("%s\n", mysql_error()));
            }

            if (mysql_query('INSERT INTO counter (name) VALUE ("users") ;', $link)) {
                printf("default counter::counter.value initialized\n");
            } else {
                die(sprintf("%s\n", mysql_error()));
            }
        } else {
            die(sprintf('could not connect to %s', $config['host']));
        }
    } else {
        die('could not call counter_initialize from non-CLI mode');
    }
}

/**
 * Атомарный инкремент счетчика
 * @return false|int новое значение счетчика либо FALSE в случае чего-либо плохого
 */
function counter_increment() {
    $config = counter_config();
    $link = mysql_connect($config['host'], $config['user'], $config['password']);
    if ($link) {
        mysql_select_db('counter');
        if (mysql_query('UPDATE counter SET value = LAST_INSERT_ID(value + 1) WHERE name = "users";', $link)) {
            if ($result = mysql_query('SELECT LAST_INSERT_ID();', $link)) {
                $data = mysql_fetch_row($result);
                return $data[0];
            }
        }
    }
    return false;
}
