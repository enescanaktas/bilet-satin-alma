<?php

namespace App\Controllers;

use App\Session;
use App\CSRF;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Session::has('user_id')) {
            header('Location: /seats');
            exit;
        }
        
        $csrfToken = CSRF::getToken();
        $error = Session::get('error');
        Session::delete('error');
        
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!CSRF::validateToken($csrfToken)) {
            Session::set('error', 'Invalid CSRF token');
            header('Location: /login');
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if ($user) {
            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            header('Location: /seats');
            exit;
        } else {
            Session::set('error', 'Invalid username or password');
            header('Location: /login');
            exit;
        }
    }

    public function logout(): void
    {
        Session::destroy();
        header('Location: /login');
        exit;
    }
}
