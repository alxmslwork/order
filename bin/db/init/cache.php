<?php
/**
 * Скрипт прогревания кеша поиска заказов
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/order/order.php';
include $baseDir . '/source/ordr/cache/cache.php';

$config = order_config();
foreach($config['shard'] as $k => $v) {
    $link = mysql_connect($v['host'], $v['user'], $v['password']);
    if ($link) {
        mysql_select_db($v['db'], $link);
        $result = mysql_query('SELECT * FROM `order` WHERE deleted = false AND executor_id IS NULL;', $link);
        $orders = [];
        while ($row = mysql_fetch_assoc($result)) {
            cache_add($row);
        }
    }
}
