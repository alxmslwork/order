<?php
/**
 * Метод API получения данных сессии пользователя
 */

session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
} else {
    return [
        'session' => $_SESSION['profile'],
    ];
}
