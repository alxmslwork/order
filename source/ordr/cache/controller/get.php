<?php

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}

if ($_SESSION['profile']['type'] == 1) {
    $offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
    if ($offset === false) {
        $offset = 0;
    }

    includeModule('cache');
    return [
        'orders' => cache_get($_GET['order'], $_GET['type'], $offset),
    ];
} else {
    return [
        'error' => 'permission denied',
    ];
}
