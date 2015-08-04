<?php
/**
 * Бутстрап проекта
 * @author alxmsl
 */
define('APPLICATION_ROOT', $_SERVER['APPLICATION_ROOT']);
define('SOURCE_ROOT', APPLICATION_ROOT . 'source/ordr/');

$method = $_SERVER['method'];
$fileName = sprintf('%ssource/%s/%s.php'
    , APPLICATION_ROOT
    , str_replace('.', '/', $method)
    , strtolower($_SERVER['REQUEST_METHOD']));

function includeModule($moduleName) {
    $fileName = SOURCE_ROOT . $moduleName . '/' . $moduleName . '.php';
    if (file_exists($fileName)) {
        include $fileName;
    } else {
        echo json_encode([
            'error' => sprintf('module %s not found at %s', $moduleName, $fileName),
        ]);
        exit(-1);
    }
}

if (file_exists($fileName)) {
    $result = include $fileName;
    echo json_encode($result);
    exit(0);
} else {
    echo json_encode([
        'error' => sprintf('method file %s not found', $fileName),
    ]);
    exit(-1);
}
