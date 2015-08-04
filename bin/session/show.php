<?php
/**
 *
 * @author alxmsl
 */
$baseDir = realpath('.');
include $baseDir . '/source/ordr/session/session.php';
var_dump(session_get($argv[1]));
