<?php
/**
 * Контроллер метода API выхода пользователя из системы
 * @author alxmsl
 */

session_start();
$_SESSION = [];
// Состариваем куку
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000
        , $params['path']
        , $params['domain']
        , $params['secure']
        , $params['httponly']);
}
// Удаляем сессию
session_destroy();
header('Location: /index.html');
