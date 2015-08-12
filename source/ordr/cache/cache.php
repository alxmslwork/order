<?php
/**
 * Модуль для работы с кешем заказов.
 * Кеш заказов здесь - это поисковый механизм для их отображения исполнителю. Этот кеш должен быть всегда прогретым,
 *  потому что другого способа получения активных заказов для исполнителя нет. Для хранения используется redis потому,
 *  что он сохраняет свое состояние в отличие от memcache. В случае если кеш почему-то остыл, есть утилита
 *  bin/db/init/cache, которая осуществляет нагревание кеша по состоянию БД. Если вдруг ломается redis, нужно поднять
 *  новый инстанс или восстановить старый и прогреть кеш вышеуказанной утилитой
 * Дополнительно присутсвует кеш данных по заказам. Архитектурно представлен кольцом консистентного хеширования на
 *  группе редисов. Если заказов будет супер много, нужно увеличивать размер кольца для распределения нагрузки по разным
 *  сущностям redis
 */

/**
 * @var Redis[]|RedisArray[] коннекторы к инстансам поискового механизма
 */
$GLOBALS['REDIS'] = [];

/**
 * @return array настройки инстансов поиска заказов
 */
function cache_config() {
    return [
        'updated' => [          // один инстанс
            'host' => 'redis',
        ],
        'price' => [            // один инстанс
            'host' => 'redis',
        ],
        'orders' => ['redis'],  // консистентное кольцо инстансов
    ];
}

/**
 * Функция получения экземпляра соединения для инстансов поиска заказов
 * @param string $name наименование индекса поиска. Возможные варианты:
 *      updated - индекс для поиска заказов по дате изменения
 *      price - индекс для поиска заказов по цене
 *      orders - индект для поиска заказов по идентификатору
 * @return Redis|RedisArray экземпляр соединения со стореджем
 */
function cache_getconnection($name) {
    if (!array_key_exists($name, $GLOBALS['REDIS'])) {
        $config = cache_config();
        if (isset($config[$name]['host'])) {
            $GLOBALS['REDIS'][$name] = new Redis();
            $GLOBALS['REDIS'][$name]->connect($config[$name]['host']);
        } else {
            $GLOBALS['REDIS'][$name] = new RedisArray($config[$name]);
        }
    }
    return $GLOBALS['REDIS'][$name];
}

/**
 * Функция добавления заказа в поиисковый механизм
 * @param array $order массив данных заказа
 */
function cache_add($order) {
    // Заказ добавляется в кольцо инстансов данных заказа
    try {
        $data   = json_encode($order);
        $Redis3 = cache_getconnection('orders');
        $Redis3->set($order['order_id'], $data);
    } catch (RedisException $Ex) {}
    // Прогревается индекс поиска заказов по дате изменения
    try {
        $Redis1 = cache_getconnection('updated');
        $Redis1->zAdd('updated', $order['updated'], $order['order_id']);
    } catch (RedisException $Ex) {}
    // Прогревается индекс поиска заказов по цене
    try {
        $Redis2 = cache_getconnection('price');
        $Redis2->zAdd('price', $order['price'], $order['order_id']);
    } catch (RedisException $Ex) {}
}

/**
 * Функкция обновления данных заказа в поисковом механизме
 * @param $order
 */
function cache_update($order) {
    // Атомарная функция удаления и добавления данных заказа
    $update = <<<EOD
    redis.call('zrem', KEYS[1], ARGV[1])
    redis.call('zadd', KEYS[1], ARGV[2] ARGV[1])
EOD;

    // Заказ обновляется в кольце инстансов данных заказа
    try {
        $data   = json_encode($order);
        $Redis3 = cache_getconnection('orders');
        $Redis3->set($order['order_id'], $data);
    } catch (RedisException $Ex) {}
    // Прогревается индекс поиска заказов по дате изменения
    try {
        $Redis1 = cache_getconnection('updated');
        $Redis1->eval($update, ['updated', $order['order_id'], $order['updated']], 1);
    } catch (RedisException $Ex) {}
    // Прогревается индекс поиска заказов по цене
    try {
        $Redis2 = cache_getconnection('price');
        $Redis2->eval($update, ['price', $order['order_id'], $order['price']], 1);
    } catch (RedisException $Ex) {}
}

/**
 * Функция удаления заказа из активных (при его выполнении)
 * @param int $orderId идентификатор заказа
 */
function cache_delete($orderId) {
    // Очистка индекса поиска заказов по дате изменения
    try {
        $Redis1 = cache_getconnection('updated');
        $Redis1->zRem('updated', $orderId);
    } catch (RedisException $Ex) {}
    // Очистка индекса поиска заказов по цене
    try {
        $Redis1 = cache_getconnection('price');
        $Redis1->zRem('price', $orderId);
    } catch (RedisException $Ex) {}
}

/**
 * Фукнция получения заказов из механизма поиска
 * @param string $order используемый индекс для поиска
 * @param string $type тип сортировки. ОДЗ: asc, desc. По-умолчанию desc
 * @param int $offset смещение по выборке заказов в порядке сортировки
 * @return array массив данных выбранных заказов
 */
function cache_get($order, $type, $offset) {
    $Redis1 = cache_getconnection($order);
    switch ($type) {
        case 'asc':
            $data = $Redis1->zRange($order, $offset, $offset + ORDERS_PER_PAGE - 1);
            break;
        case 'desc':
            $data = $Redis1->zRevRange($order, $offset, $offset + ORDERS_PER_PAGE - 1);
            break;
    }
    /** @var RedisArray $Redis2 */
    $Redis2 = cache_getconnection('orders');
    $data   = $Redis2->mget($data);
    $orders = [];
    foreach($data as $v) {
        $orders[] = json_decode($v, true);
    }
    return $orders;
}
