<?php

session_start();
if(!isset($_SESSION['visit']))
{
    echo "This is the first time you're visiting this server\n";
    $_SESSION['visit'] = 0;
}
else
    echo "Your number of visits: ".$_SESSION['visit'] . "\n";

$_SESSION['visit']++;

echo "Client IP: ".$_SERVER['REMOTE_ADDR'] . "\n";
print_r($_COOKIE);
print_r($_SESSION);
