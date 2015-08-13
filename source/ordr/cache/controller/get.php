<?php
/**
 * Метод API получения активных заказов
 * Метод доступен только для исполнителей, для заказчиков должен возвращать ошибку
 */

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: /index.html');
    exit(0);
} else {
    // Если пользователь является исполнителем
    if ($_SESSION['profile']['type'] == 1) {
        // валидируем целочисленный отступ
        $offset = filter_var($_GET['offset'], FILTER_VALIDATE_INT);
        if ($offset === false) {
            $offset = 0;
        }

        // ОДЗ на испольуземый индекс
        switch ($_GET['order']) {
            case 'price':
                $order = 'price';
                break;
            case 'date':
            default:
                $order = 'updated';
                break;
        }

        // ОДЗ на тип сортировки
        switch ($_GET['type']) {
            case 'asc':
            case 'desc':
                $type = $_GET['type'];
                break;
            default:
                $type = 'desc';
                break;
        }

        includeModule('cache');
        return [
            'orders' => cache_get($order, $type, $offset),
        ];
    } else {
        return [
            'error' => 'permission denied',
        ];
    }
}
