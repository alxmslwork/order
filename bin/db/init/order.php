<?php
/**
 * Скрипт инициализации шардовых стореджей профиля пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/order/order.php';
order_initialize();
