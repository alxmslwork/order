<?php
/**
 * Скрипт инициализации шардовых стореджей профиля пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/user/user.php';
profile_initialize();