<?php
/**
 * Модуль для работы с блокировками на изменение заказов. Блокировки используют систему консистентного хеширования на
 *  базе стореджей memcached.
 * В качестве блокировки выступает какое-то значение в сторедже. При взятии блокировки происходит попытка создать сие
 *  значение. Если оно создано ранее, функция ждет некоторое время, в течение которого повторяет попытки с некоторым
 *  интервалом. Снятие блокировки происходит удалением данного значения из стореджа
 * @author alxmsl
 */

function lock_config() {
    return [
        [
            'host' => 'cachesession1',
        ],
        [
            'host' => 'cachesession2',
        ],
    ];
}

/**
 * @return mixed соединение с пулом кешей хранения сессий пользователя
 */
function lock_getconnection() {
    $connection = null;
    $config     = lock_config();
    foreach($config as $v) {
        if (is_null($connection)) {
            $connection = memcache_connect($v['host']);
        } else {
            memcache_add_server($connection, $v['host']);
        }
    }
    return $connection;
}

/**
 * Функция взятия блокировки для изменения заказа
 * @param int $orderId идентификатор заказа
 * @param int $wait время ожидания блокировки, если она есть, сек
 * @param int $ttl время жизни блокировки, сек
 * @return bool результат взятия блокировки
 */
function lock_lock($orderId, $wait, $ttl) {
    $key        = sprintf('ordr_%s', $orderId);
    $connection = lock_getconnection();
    $result     = memcache_add($connection, $key, 1, false, $ttl);
    if ($result === false) {
        $end = microtime() + $wait * 1000;
        for (; microtime() < $end;) {
            usleep(100000);
            $result = memcache_add($connection, $key, 1, false, $ttl);
            if ($result === true) {
                return true;
            }
        }
        return false;
    } else {
        return true;
    }
}

/**
 * Функция освобождения блокировки
 * @param int $orderId идентификатор заказа
 */
function lock_unlock($orderId) {
    $key        = sprintf('ordr_%s', $orderId);
    $connection = lock_getconnection();
    memcache_delete($connection, $key);
}
