<?php
/**
 * Скрипт инициализации шардовых стореджей карты логинов пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/map/map.php';
authorizer_initialize();
