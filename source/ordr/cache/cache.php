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
    $data  = json_encode($order);
    //@todo: заменить на LUA - одну команду
    try {
        $Redis->zAdd('updated', $order['updated'], $data);
    } catch (RedisException $Ex) {}
    try {
        $Redis->zAdd('price', $order['price'], $data);
    } catch (RedisException $Ex) {}
    return true;
}

function cache_get() {
    $Redis  = cache_getconnection();
    $data   = $Redis->zRange('updated', 0, -1);
    $orders = [];
    foreach($data as $v) {
        $orders[] = json_decode($v, true);
    }
    return $orders;
}
