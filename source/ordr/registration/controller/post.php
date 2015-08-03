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
$type = $_POST['type'];
switch ($type) {
    case 'customer':
    case 'executor':
        break;
    default:
        return [
            'error' => 'invalid user type',
        ];
}

//@todo: создание нового пользователя

/**
 * @todo: проверить, что данного пользователя не существует или существует в карте пользователей
 * если карта недоступна, регистрироваться нельзя
 * карта шардится по логинам пользователей
 * для карты есть конфиг
 */
includeModule('map');
if (map_add($login)) {


    return [
        'completed' => true,
    ];
} else {
    return [
        'error' => 'registration error',
    ];
}
