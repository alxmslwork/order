<?php
session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}

if ($_SESSION['profile']['type'] == 0) {
    $description = filter_var($_POST['description'], FILTER_VALIDATE_REGEXP, [
        'options' => [
            'regexp' => '/[0-9A-Za-zА-Яа-я\s]{5,20}/',
        ],
    ]);
    if ($description === false) {
        return [
            'error' => 'invalid description value',
        ];
    }
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT);
    if ($price === false && $price < 100) {
        return [
            'error' => 'invalid price value',
        ];
    }

    includeModule('order');
    $order = null;
    if (array_key_exists('order', $_SERVER)) {
        $orderId = $_SERVER['order'];
        $order = order_get($orderId, $_SESSION['profile']['user_id']);
        if ($order === false) {
            $order = null;
        }
    }

    if (is_null($order)) {
        includeModule('counter');
        $orderId = counter_increment('orders');

        $order = order_add($_SESSION['profile']['user_id'], $orderId, $description, $price);
        if ($order !== false) {
            includeModule('cache');
            cache_add($order);
            return [
                'completed' => true,
            ];
        } else {
            return [
                'error' => 'service temporary unavailable',
            ];
        }
    } else {
        $order = order_update($_SESSION['profile']['user_id'], $order['order_id'], $description, $price);
        if ($order !== false) {
            includeModule('cache');
            cache_update($order);
            return [
                'completed' => true,
            ];
        } else {
            return [
                'error' => 'service temporary unavailable',
            ];
        }
    }
} else {
    return [
        'error' => 'permission denied',
    ];
}
