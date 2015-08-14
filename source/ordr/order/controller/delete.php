<?php
/**
 * Метод API удаления заказа пользователя
 */

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: /index.html');
    exit(0);
} else {
    // Если пользователь является заказчиком, ему доступна данная операция
    if ($_SESSION['profile']['type'] == 0) {
        $order = null;
        if (array_key_exists('order', $_SERVER)) {
            $orderId = filter_var($_SERVER['order'], FILTER_VALIDATE_INT);
            if ($orderId !== false) {
                includeModule('order');
                // Атомарно отмечаем заказ удаленным
                if (lock_lock($orderId, 2, 1)) {
                    if (order_delete($orderId, $_SESSION['profile']['user_id'])) {
                        // Если удалось удалить заказ, прогреваем кеш для поиска заказов соответсвующим образом
                        includeModule('cache');
                        cache_delete($orderId);
                        lock_unlock($orderId);
                        return [
                            'completed' => true,
                        ];
                    } else {
                        lock_unlock($orderId);
                    }
                } else {
                    return [
                        'error' => 'order locked',
                    ];
                }
            }
        }
        return [
            'error' => 'order not found',
        ];
    } else {
        return [
            'error' => 'permission denied',
        ];
    }
}
