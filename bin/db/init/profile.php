<?php
/**
 * Скрипт инициализации шардовых стореджей профиля пользователей
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/profile/profile.php';
profile_initialize();
