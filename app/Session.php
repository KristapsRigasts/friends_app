<?php

namespace App;

class Session
{
    public static function isAuthorized():bool
    {
        return isset($_SESSION['auth_id']);
    }

    public static function create(string $key, $value):void
    {
        $_SESSION[$key] = $value;
    }
}