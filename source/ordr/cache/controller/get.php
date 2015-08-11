<?php

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}
if ($_SESSION['profile']['type'] == 1) {
    includeModule('cache');
    return [
        'orders' => cache_get(),
    ];
} else {
    return [
        'error' => 'permission denied',
    ];
}
