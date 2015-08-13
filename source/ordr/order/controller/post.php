<?php
/**
 * Метод API сохранения заказа пользователя при добавлении или редактировании
 */

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: /index.html');
    exit(0);
} else {
    // Данный функционал доступен только для заказчика
    if ($_SESSION['profile']['type'] == 0) {

        // ОДЗ описания заказа
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

        // ОДЗ стоимости заказа
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT);
        if ($price === false && $price < 100) {
            return [
                'error' => 'invalid price value',
            ];
        }

        /**
         * Определяем данные заказа для редактирования
         * Для редактирования нам интересен только идентификатор заказа, который является постоянной величиной, поэтому
         *  можно использовать неблокирующие операции
         */
        includeModule('order');
        $order = null;
        if (array_key_exists('order', $_SERVER)) {
            $orderId = filter_var($_SERVER['order'], FILTER_VALIDATE_INT);
            if ($orderId !== false) {
                $order = order_get($orderId, $_SESSION['profile']['user_id']);
                if ($order === false) {
                    $order = null;
                }
            }
        }

        if (is_null($order)) {
            /**
             * Если происходит добавление нового заказа, атомарно получаем униклаьный идентификатор для него, после чего
             *  во избежание конкурентных запросов на редактирование в блокировке производим изменения данных в
             *  хранилищах: БД заказов и поискавых инстансах
             */
            includeModule('counter');
            $orderId = counter_increment('orders');

            // Добавляем новый заказ с данным уникальным идентификатором
            includeModule('lock');
            if (lock_lock($orderId, 2, 1)) {
                $order = order_add($_SESSION['profile']['user_id'], $orderId, $description, $price);
                if ($order !== false) {
                    // В случае успешного добавления греем кеш поиска заказов
                    includeModule('cache');
                    cache_add($order);
                    lock_unlock($orderId);
                    return [
                        'completed' => true,
                    ];
                } else {
                    lock_unlock($orderId);
                    return [
                        'error' => 'service temporary unavailable',
                    ];
                }
            } else {
                return [
                    'error' => 'order locked',
                ];
            }
        } else {
            /**
             * Если происходит редактирование заказа, изменяем его поля атомарно, в блокировке, чтобы данные БД и
             *  кеша совпадали при конкурентных запросах
             */
            includeModule('lock');
            if (lock_lock($order['order_id'], 2, 1)) {
                $order = order_update($_SESSION['profile']['user_id'], $order['order_id'], $description, $price);
                if ($order !== false) {
                    includeModule('cache');
                    cache_update($order);
                    lock_unlock($order['order_id']);
                    return [
                        'completed' => true,
                    ];
                } else {
                    lock_unlock($order['order_id']);
                    return [
                        'error' => 'service temporary unavailable',
                    ];
                }
            } else {
                return [
                    'error' => 'order locked',
                ];
            }
        }
    } else {
        return [
            'error' => 'permission denied',
        ];
    }
}
