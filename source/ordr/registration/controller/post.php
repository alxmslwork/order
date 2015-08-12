<?php
/**
 * Метод API выполнения регистрации пользователя
 * @author alxmsl
 */

// ОДЗ на логин пользователя
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

// ОДЗ на пароль пользователя
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

// ОДЗ на тип пользователя
$type = $_POST['type'];
switch ($type) {
    case 'customer':
        $userType = 0;
        break;
    case 'executor':
        $userType = 1;
        break;
    default:
        return [
            'error' => 'invalid user type',
        ];
}

includeModule('authorizer');
// Производим сохранение данных пользователя в авторайзер с шардингом по логину
if (authorizer_add($login, $password)) {
    includeModule('counter');
    // В случае успешного добавления, атомарно выделяем пользователю идентификатор для хранения данных профиля
    $userId = counter_increment('users');
    if ($userId !== false) {
        includeModule('profile');
        // Сохраняем данные профиля пользователя
        if (profile_add($userId, $login, $userType)) {
            // Атомарно связываем данные авторайзера и профиля пользователя
            if (authorizer_update($login, $userId)) {
                return [
                    'completed' => true,
                ];
            } else {
                return [
                    'error' => 'map logic error',
                ];
            }
        } else {
            return [
                'error' => 'user logic error',
            ];
        }
    } else {
        return [
            'error' => 'user counter unavailable',
        ];
    }
} else {
    return [
        'error' => 'user already created',
    ];
}
