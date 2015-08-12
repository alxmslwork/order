<?php
/**
 * Модуль для работы с сессиями пользователей
 */

/**
 * @return array настройки шардов хранения сессий пользователя
 */
function session_config() {
    return [
        [
            'host' => 'cachesession1',
        ],
        [
            'host' => 'cachesession2',
        ],
    ];
}

/**
 * @return mixed соединение с пулом кешей хранения сессий пользователя
 */
function session_getconnection() {
    $connection = null;
    $config     = session_config();
    foreach($config as $v) {
        if (is_null($connection)) {
            $connection = memcache_connect($v['host']);
        } else {
            memcache_add_server($connection, $v['host']);
        }
    }
    return $connection;
}

/**
 * Функция получения данных сессии
 * @param string $sessionId идентификатор сессии
 * @return mixed данные и сессии
 */
function session_get($sessionId) {
    $connection = session_getconnection();
    return memcache_get($connection, $sessionId);
}

