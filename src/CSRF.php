<?php

namespace App;

class CSRF
{
    public static function generateToken(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    public static function getToken(): string
    {
        Session::start();
        if (!Session::has('csrf_token')) {
            return self::generateToken();
        }
        return Session::get('csrf_token');
    }

    public static function validateToken(string $token): bool
    {
        Session::start();
        $sessionToken = Session::get('csrf_token');
        
        if ($sessionToken === null) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }

    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
