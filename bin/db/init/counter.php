<?php
/**
 * Скрипт инициализации стораджа счетчиков
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/counter/counter.php';
counter_initialize();
