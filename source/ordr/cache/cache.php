<?php

$GLOBALS['REDIS'] = null;

function cache_config() {
    return [
        'host'     => 'redis',
    ];
}

function cache_getconnection() {
    if (is_null($GLOBALS['REDIS'])) {
        $config = cache_config();
        $GLOBALS['REDIS'] = new Redis();
        $GLOBALS['REDIS']->connect($config['host']);
    }
    return $GLOBALS['REDIS'];
}

function cache_add($order) {
    $Redis = cache_getconnection();
    $data  = sprintf('%010d:%s', $order['order_id'], json_encode($order));
    $add   = <<<EOD
    redis.call('zadd', 'updated', ARGV[1], ARGV[3])
    redis.call('zadd', 'price', ARGV[2], ARGV[3])
EOD;
    try {
        $Redis->eval($add, [$order['updated'], $order['price'], $data]);
    } catch (RedisException $Ex) {}
    try {
        $Redis->zAdd('price', $order['price'], $data);
    } catch (RedisException $Ex) {}
}

function cache_update($order) {
    $Redis  = cache_getconnection();
    $key1   = sprintf('(%010d', $order['order_id']);
    $key2   = sprintf('(%010d', $order['order_id'] + 1);
    $data   = sprintf('%010d:%s', $order['order_id'], json_encode($order));
    $update = <<<EOD
    redis.call('zremrangebylex', 'updated', ARGV[1], ARGV[2])
    redis.call('zremrangebylex', 'price', ARGV[1], ARGV[2])
    redis.call('zadd', 'updated', ARGV[3], ARGV[5])
    redis.call('zadd', 'price', ARGV[4], ARGV[5])
EOD;

    try {
        $Redis->eval($update, [$key1, $key2, $order['updated'], $order['price'], $data]);
    } catch (RedisException $Ex) {}
}

function cache_delete($orderId) {
    $Redis  = cache_getconnection();
    $key1   = sprintf('(%010d', $orderId);
    $key2   = sprintf('(%010d', $orderId + 1);
    $remove = <<<EOD
    redis.call('zremrangebylex', 'updated', ARGV[1], ARGV[2])
    redis.call('zremrangebylex', 'price', ARGV[1], ARGV[2])
EOD;

    try {
        $Redis->eval($remove, [$key1, $key2]);
    } catch (RedisException $Ex) {}
}

function cache_get() {
    $Redis  = cache_getconnection();
    $data   = $Redis->zRange('updated', 0, -1);
    $orders = [];
    foreach($data as $v) {
        $p = explode(':', $v, 2);
        $orders[] = json_decode($p[1], true);
    }
    return $orders;
}
