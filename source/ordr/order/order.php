<?php
/**
 * @return array настройки шардов хранения заказов пользователей
 */
function order_config() {
    return [
        'shard' => [
            2 => [
                'db'       => 'order1',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
            100000 => [
                'db'       => 'order2',
                'host'     => 'mysql',
                'user'     => 'root',
                'password' => 'secret',
            ],
        ],
    ];
}

/**
 * Фукнция инициализации БД заказов
 * Испольузется только для CLI
 */
function order_initialize() {
    if (PHP_SAPI == 'cli') {
        $config = order_config();
        foreach($config['shard'] as $k => $v) {
            $link = mysql_connect($v['host'], $v['user'], $v['password']);
            if ($link) {
                if (mysql_query(sprintf('CREATE DATABASE IF NOT EXISTS %s;', $v['db']), $link)) {
                    printf("%s: database %s created\n", $v['db'], $v['host']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }

                mysql_select_db($v['db'], $link);
                $createTableQuery = <<<EOD
CREATE TABLE IF NOT EXISTS `order` (
    order_id    INT           NOT NULL,
    customer_id INT           NOT NULL,
    description VARCHAR(50)   NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    updated     INT           NOT NULL,
    executor_id INT           DEFAULT NULL,
    deleted     BOOLEAN       DEFAULT FALSE,
    PRIMARY KEY (customer_id, order_id)
);
EOD;
                if (mysql_query($createTableQuery, $link)) {
                    printf("%s: table %s::order created\n", $v['host'], $v['db']);
                } else {
                    printf("%s: %s\n", $v['host'], mysql_error());
                }
            } else {
                printf("%s: could not connect\n", $v['host']);
            }
        }
    } else {
        die('could not call order_initialize from non-CLI mode');
    }
}

/**
 * Функция получения настроек соединения для данных заказов
 * @param int $userId идентификатор пользователя, заказы которого нас интересуют
 * @return false|array массив настроек соединения или FALSE, если плохо всё
 */
function order_getconnection($userId) {
    $config = order_config();
    foreach($config['shard'] as $k => $v) {
        if ($userId <= $k) {
            return $v;
        }
    }
    return false;
}

/**
 * Функция сохранения заказа
 * @param int $userId идентификатор пользователя
 * @param int $orderId идентификатор заказа
 * @param string $description описание заказа
 * @param float $price стоимость заказа
 * @return array|false данные добавленного заказа или FALSE
 */
function order_add($userId, $orderId, $description, $price) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            $now = time();
            if (mysql_query(sprintf('INSERT INTO `order` (order_id, customer_id, description, price, updated) VALUES (%s, %s, "%s", %s, %s);'
                , $orderId, $userId, $description, $price, $now), $link)) {

                if (mysql_affected_rows($link) == 1) {
                    return [
                        'order_id'    => $orderId,
                        'customer_id' => $userId,
                        'description' => $description,
                        'price'       => $price,
                        'updated'     => $now,
                    ];
                }
            }
        }
    }
    return false;
}

function order_update($userId, $orderId, $description, $price) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            $now = time();
            if (mysql_query(sprintf('UPDATE `order` SET description = "%s", price = %s, updated = %s WHERE order_id = %s AND customer_id = %s AND deleted = false AND executor_id IS NULL;'
                , $description, $price, $now, $orderId, $userId), $link)) {

                if (mysql_affected_rows($link) == 1) {
                    return [
                        'order_id'    => $orderId,
                        'customer_id' => $userId,
                        'description' => $description,
                        'price'       => $price,
                        'updated'     => $now,
                    ];
                }
            }
        }
    }
    return false;
}

function order_get_all($userId) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            $result = mysql_query(sprintf('SELECT * FROM `order` WHERE customer_id = %s AND deleted = false AND executor_id IS NULL;', $userId), $link);
            $orders = [];
            while ($row = mysql_fetch_assoc($result)) {
                $orders[] = $row;
            }
            return $orders;
        }
    }
    return false;
}

function order_get($orderId, $userId, $executorId = null) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (is_null($executorId)) {
                $result = mysql_query(sprintf('SELECT * FROM `order` WHERE customer_id = %s AND order_id = %s AND deleted = false AND executor_id IS NULL;'
                    , $userId, $orderId), $link);
            } else {
                $result = mysql_query(sprintf('SELECT * FROM `order` WHERE customer_id = %s AND order_id = %s AND deleted = false AND executor_id = %s;'
                    , $userId, $orderId, $executorId), $link);
            }
            return mysql_fetch_assoc($result);
        }
    }
    return false;
}

function order_delete($orderId, $userId) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            mysql_query(sprintf('UPDATE `order` SET deleted = true WHERE customer_id = %s AND order_id = %s AND deleted = false AND executor_id IS NULL;'
                , $userId, $orderId), $link);
            if (mysql_affected_rows($link) == 1) {
                return true;
            }
        }
    }
    return false;
}

function order_execute($orderId, $ownerId, $executorId) {
    $connection = order_getconnection($ownerId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            mysql_query(sprintf('UPDATE `order` SET executor_id = %s WHERE customer_id = %s AND order_id = %s AND deleted = false AND executor_id IS NULL;'
                , $executorId, $ownerId, $orderId), $link);
            if (mysql_affected_rows($link) == 1) {
                return true;
            }
        }
    }
    return false;
}
