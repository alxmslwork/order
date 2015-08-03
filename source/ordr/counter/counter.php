<?php

function counter_config() {
    return [
        'host'     => 'mysqlcommon',
        'user'     => 'root',
        'password' => 'secret',
    ];
}

/**
 *
 * @author alxmsl
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

            mysql_select_db('counter');
            $createTableQuery = <<<EOD
CREATE TABLE IF NOT EXISTS counter (
    name VARCHAR(10) NOT NULL,
    value INT DEFAULT 0 NOT NULL,
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
