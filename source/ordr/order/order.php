<?php
/**
 * Модуль работы с заказами в системе.
 * Заказы хранятся в БД, шардинг происходит по идентификатору пользвоателя системы. Шардингом можно управлять для
 *  переноса данных активных пользователей на отдельные инстансы и для группировки менее активных пользователей на
 *  других инстансах для ручного выравнивания нагруки на серверах БД
 */

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

/**
 * Функция обновления заказа.
 * Возможно только изменения заказа, выполнение которого не было произведено
 *
 * @param int $userId идентификатор пользователя
 * @param int $orderId идентификатор заказа
 * @param string $description описание заказа
 * @param float $price стоимость заказа
 * @return array|false данные измененного заказа или FALSE
 */
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

/**
 * Функция получения всех заказов пользователя.
 * Возвращаются только неудаленные и невыполненные заказы, созданные пользователем
 *
 * @param int $userId идентификатор пользователя
 * @param string $order поле сортировки заказов. ОДЗ: updated, price
 * @param string $type тип сортировки заказов: по-возрастанию или по-убыванию
 * @param int $offset смещение выборки заказов
 * @return array|bool массив заказов пользователя
 */
function order_get_all($userId, $order, $type, $offset) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            $result = mysql_query(sprintf('SELECT * FROM `order` WHERE customer_id = %s AND deleted = false AND executor_id IS NULL ORDER BY %s %s LIMIT %s, %s;'
                , $userId, $order, $type, $offset, ORDERS_PER_PAGE), $link);
            $orders = [];
            if ($result) {
                while ($row = mysql_fetch_assoc($result)) {
                    $orders[] = $row;
                }
            }
            return $orders;
        }
    }
    return false;
}

/**
 * Функция получения заказа пользователя
 * Возвращаются только неудаленный заказ пользователя
 *
 * @param int $orderId идентификатор заказа
 * @param int $userId иденитификатор хозяина заказа
 * @param null|int $executorId идентификатор исполнителя заказа
 * @return array|bool данные заказа или FALSE, если что-нибудь плохо
 */
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

/**
 * Функция удаления заказа.
 * Возможно удаление только неудаленного и невыполненного заказа пользователя
 *
 * @param int $orderId идентификатор заказа
 * @param int $userId иденитификатор хозяина заказа
 * @return bool результат удаления заказа
 */
function order_delete($orderId, $userId) {
    $connection = order_getconnection($userId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('UPDATE `order` SET deleted = true WHERE customer_id = %s AND order_id = %s AND deleted = false AND executor_id IS NULL;'
                , $userId, $orderId), $link)) {
                return (mysql_affected_rows($link) == 1);
            }
        }
    }
    return false;
}

/**
 * Функция выполнения заказа пользователя
 * @param int $orderId идентификатор заказа
 * @param int $ownerId иденитификатор хозяина заказа
 * @param int $executorId идентификатор исполнителя заказа
 * @return bool результат назначения исполнителя заказа
 */
function order_execute($orderId, $ownerId, $executorId) {
    $connection = order_getconnection($ownerId);
    if ($connection !== false) {
        $link = mysql_connect($connection['host'], $connection['user'], $connection['password']);
        if ($link) {
            mysql_select_db($connection['db'], $link);
            if (mysql_query(sprintf('UPDATE `order` SET executor_id = %s WHERE customer_id = %s AND order_id = %s AND deleted = false AND executor_id IS NULL;'
                , $executorId, $ownerId, $orderId), $link)) {
                return (mysql_affected_rows($link) == 1);
            }
        }
    }
    return false;
}
