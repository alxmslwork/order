<?php
session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}

if ($_SESSION['profile']['type'] == 1) {
    $ownerId = filter_var($_SERVER['owner'], FILTER_SANITIZE_NUMBER_INT);
    if ($ownerId === false) {
        return [
            'error' => 'invalid owner value',
        ];
    }

    includeModule('order');
    if (array_key_exists('order', $_SERVER)) {
        $orderId = $_SERVER['order'];
        if (order_execute($orderId, $ownerId, $_SESSION['profile']['user_id'])) {
            includeModule('cache');
            cache_delete($orderId);

            $order = order_get($orderId, $ownerId, $_SESSION['profile']['user_id']);
            if ($order !== false) {
                $salary = $order['price'] * .1;
                includeModule('payment');
                if (payment_add($_SESSION['profile']['user_id'], $order['order_id'], $salary)) {
                    includeModule('profile');
                    if (profile_update($_SESSION['profile']['user_id'], $salary)) {
                        $_SESSION['profile']['money'] += $salary;
                        return [
                            'completed' => true,
                        ];
                    } else {
                        return [
                            'error' => 'technical error 1',
                        ];
                    }
                } else {
                    return [
                        'error' => 'technical error 2',
                    ];
                }
            } else {
                return [
                    'error' => 'technical error 3',
                ];
            }
        } else {
            return [
                'error' => 'order not found',
            ];
        }
    } else {
        return [
            'error' => 'service temporary unavailable',
        ];
    }
} else {
    return [
        'error' => 'permission denied',
    ];
}
