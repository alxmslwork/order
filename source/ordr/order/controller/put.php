<?php
/**
 * Метод API для выполнения заказа
 */

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
} else {
    // Выполнение заказа возможно только для исполнителей
    if ($_SESSION['profile']['type'] == 1) {
        $ownerId = filter_var($_SERVER['owner'], FILTER_VALIDATE_INT);
        if ($ownerId !== false) {
            includeModule('order');
            if (array_key_exists('order', $_SERVER)) {
                $orderId = filter_var($_SERVER['order'], FILTER_VALIDATE_INT);
                if ($orderId !== false) {
                    // Выполняем атомарную отметку заказа выполненным
                    includeModule('lock');
                    if (lock_lock($orderId, 2, 1)) {
                        if (order_execute($orderId, $ownerId, $_SESSION['profile']['user_id'])) {

                            // В случае успешного выполнения чистим поисковый кеш от записей для выборки
                            includeModule('cache');
                            cache_delete($orderId);
                            lock_unlock($orderId);

                            /**
                             * Запускаем меанизм начисление благ исполнителю. Для чего:
                             *  1. Выполняем получение данных заказа указанного заказчика и исполнителя по идентификатору
                             *      заказа
                             *  2. Вычисляем зарплату
                             *  3. Сохраняем данные о платеже пользователя на всякий случай
                             *  4. Атомарно инкрементим баланс исполнителя
                             */
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
                            'error' => 'order locked',
                        ];
                    }
                } else {
                    return [
                        'error' => 'invalid order value',
                    ];
                }
            } else {
                return [
                    'error' => 'service temporary unavailable',
                ];
            }
        } else {
            return [
                'error' => 'invalid owner value',
            ];
        }
    } else {
        return [
            'error' => 'permission denied',
        ];
    }
}
