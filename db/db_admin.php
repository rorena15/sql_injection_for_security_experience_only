<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function check_csrf($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}


