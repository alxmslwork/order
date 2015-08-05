<?php
/**
 * Скрипт инициализации шардовых стореджей карты логинов пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/authorizer/authorizer.php';
authorizer_initialize();
