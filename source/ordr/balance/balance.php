<?php
/**
 * Модуль для управления балансом системы
 * @author alxmsl
 */

/**
 * @return array конфигурационные данные стореджа баланса
 */
function balance_config() {
    return [
        'db'       => 'balance',
        'host'     => 'mysql',
        'user'     => 'root',
        'password' => 'secret',
    ];
}

/**
 * Фукнция инициализации БД баланса
 * Испольузется только для CLI
 */
function balance_initialize() {
    if (PHP_SAPI == 'cli') {
        $config = balance_config();
        $link = mysql_connect($config['host'], $config['user'], $config['password']);
        if ($link) {
            if (mysql_query(sprintf('CREATE DATABASE IF NOT EXISTS %s;', $config['db']), $link)) {
                printf("database %s created\n", $config['db']);
            } else {
                die(sprintf("%s\n", mysql_error()));
            }

            mysql_select_db($config['db'], $link);
            $createTableQuery = <<<EOD
CREATE TABLE IF NOT EXISTS balance (
    name  VARCHAR(10)   NOT NULL,
    value DECIMAL(10,2) DEFAULT 0 NOT NULL,
    PRIMARY KEY (name)
);
EOD;
            if (mysql_query($createTableQuery, $link)) {
                printf("table %s::balance created\n", $config['db']);
            } else {
                die(sprintf("%s\n", mysql_error()));
            }

            if (mysql_query('INSERT INTO balance (name) VALUE ("system") ;', $link)) {
                printf("default %s::balance.system initialized\n", $config['db']);
            } else {
                die(sprintf("%s\n", mysql_error()));
            }
        } else {
            die(sprintf('could not connect to %s', $config['host']));
        }
    } else {
        die('could not call balance_initialize from non-CLI mode');
    }
}

/**
 * Атомарный инкремент баланса
 * @param float $value значение нового баланса системы
 * @return bool новое значение счетчика либо FALSE в случае чего-либо плохого
 */
function balance_increment($value) {
    $config = balance_config();
    $link = mysql_connect($config['host'], $config['user'], $config['password']);
    if ($link) {
        mysql_select_db($config['db'], $link);
        if (mysql_query(sprintf('UPDATE balance SET value = value + %s WHERE name = "system";', $value)
            , $link)) {
            return (mysql_affected_rows($link) == 1);
        }
    }
    return false;
}

/**
 * Поулчение баланса системы
 * @return false|float значение баланса системы
 */
function balance_get() {
    $config = balance_config();
    $link = mysql_connect($config['host'], $config['user'], $config['password']);
    if ($link) {
        mysql_select_db($config['db'], $link);
        $result = mysql_query('SELECT * FROM balance WHERE name = "system";', $link);
        if ($result) {
            $row = mysql_fetch_assoc($result);
            return $row['value'];
        }
    }
    return false;
}
