<?php

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}

if ($_SESSION['profile']['type'] == 0) {
    $order = null;
    if (array_key_exists('order', $_SERVER)) {
        $orderId = $_SERVER['order'];
        includeModule('order');
        if (order_delete($orderId, $_SESSION['profile']['user_id'])) {
            return [
                'completed' => true,
            ];
        } else {
            return [
                'error' => 'order not found',
            ];
        }
    } else {
        return [
            'error' => 'order not found',
        ];
    }
} else {
    return [
        'error' => 'permission denied',
    ];
}
