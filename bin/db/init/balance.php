<?php
/**
 * Скрипт инициализации стореджей карты логинов пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/balance/balance.php';
balance_initialize();
