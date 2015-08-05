<?php
/**
 * Контроллер выполнения регистрации
 * @author alxmsl
 */
$login = filter_var($_POST['login'], FILTER_VALIDATE_REGEXP, [
    'options' => [
        'regexp' => '/[a-z]{1,5}/',
    ],
]);
if ($login === false) {
    return [
        'error' => 'invalid login value',
    ];
}
$password = filter_var($_POST['password'], FILTER_VALIDATE_REGEXP, [
    'options' => [
        'regexp' => '/[A-z0-9]{5,32}/',
    ],
]);
if ($password === false) {
    return [
        'error' => 'invalid password value',
    ];
}

includeModule('authorizer');
$userId = authorizer_check($login, $password);
if ($userId > 0) {
    includeModule('profile');
    $profile = profile_get($userId);
    if ($profile !== false) {
        session_start();
        $_SESSION['profile'] = $profile;
        return [
            'completed' => true,
        ];
    } else {
        return [
            'error' => 'user data unavailable',
        ];
    }
} else {
    switch ($userId) {
        case -1:
            return [
                'error' => 'service unavailable',
            ];
        case -2:
            return [
                'error' => 'user not found',
            ];
        case -3:
            return [
                'error' => 'password incorrect',
            ];
        default:
            return [
                'error' => 'unknown error',
            ];
    }
}
