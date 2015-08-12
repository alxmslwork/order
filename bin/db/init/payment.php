<?php
/**
 * Скрипт инициализации стореджей платежей пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/payment/payment.php';
payment_initialize();
