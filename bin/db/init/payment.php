<?php
/**
 * Скрипт инициализации шардовых стореджей профиля пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/payment/payment.php';
payment_initialize();
