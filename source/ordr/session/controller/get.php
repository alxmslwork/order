<?php
session_start();
if (!isset($_SESSION['profile'])) {
    header('Location: index.html');
    exit(0);
}

return [
    'session' => $_SESSION['profile'],
];
