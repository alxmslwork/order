<?php
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

function session_get($sessionId) {
    $connection = session_getconnection();
    return memcache_get($connection, $sessionId);
}

