<?php

$GLOBALS['REDIS'] = [];

function cache_config() {
    return [
        'updated' => [
            'host' => 'redis',
        ],
        'price' => [
            'host' => 'redis',
        ],
        'orders' => ['redis'],
    ];
}

/**
 * @param $name
 * @return Redis
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

function cache_add($order) {
    try {
        $data   = json_encode($order);
        $Redis3 = cache_getconnection('orders');
        $Redis3->set($order['order_id'], $data);
    } catch (RedisException $Ex) {}
    try {
        $Redis1 = cache_getconnection('updated');
        $Redis1->zAdd('updated', $order['updated'], $order['order_id']);
    } catch (RedisException $Ex) {}
    try {
        $Redis2 = cache_getconnection('price');
        $Redis2->zAdd('price', $order['price'], $order['order_id']);
    } catch (RedisException $Ex) {}
}

function cache_update($order) {
    $update = <<<EOD
    redis.call('zrem', KEYS[1], ARGV[1])
    redis.call('zadd', KEYS[1], ARGV[2] ARGV[1])
EOD;

    try {
        $data   = json_encode($order);
        $Redis3 = cache_getconnection('orders');
        $Redis3->set($order['order_id'], $data);
    } catch (RedisException $Ex) {}
    try {
        $Redis1 = cache_getconnection('updated');
        $Redis1->eval($update, ['updated', $order['order_id'], $order['updated']], 1);
    } catch (RedisException $Ex) {}
    try {
        $Redis2 = cache_getconnection('price');
        $Redis2->eval($update, ['price', $order['order_id'], $order['price']], 1);
    } catch (RedisException $Ex) {}
}

function cache_delete($orderId) {
    try {
        $Redis1 = cache_getconnection('updated');
        $Redis1->zRem('updated', $orderId);
    } catch (RedisException $Ex) {}
    try {
        $Redis1 = cache_getconnection('price');
        $Redis1->zRem('price', $orderId);
    } catch (RedisException $Ex) {}
}

function cache_get($order, $orderType, $offset) {
    $offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
    if ($offset === false) {
        $offset = 0;
    }
    switch ($order) {
        case 'price':
            $key = 'price';
            break;
        case 'date':
        default:
            $key = 'updated';
            break;
    }
    $Redis1 = cache_getconnection($key);
    switch ($orderType) {
        case 'asc':
            $data = $Redis1->zRange($key, $offset, $offset + ORDERS_PER_PAGE - 1);
            break;
        case 'desc':
        default:
            $data = $Redis1->zRevRange($key, $offset, $offset + ORDERS_PER_PAGE - 1);
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
