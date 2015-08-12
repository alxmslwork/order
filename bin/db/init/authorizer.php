<?php
/**
 * Скрипт инициализации стореджей карты логинов пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/authorizer/authorizer.php';
authorizer_initialize();
