<?php
/**
 * Скрипт инициализации стореджей заказов
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/order/order.php';
order_initialize();
