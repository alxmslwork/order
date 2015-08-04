<?php
/**
 * Скрипт инициализации стораджа счетчика
 * @author alxmsl
 */

$baseDir = realpath('.');
include $baseDir . '/source/ordr/counter/counter.php';
counter_initialize();
