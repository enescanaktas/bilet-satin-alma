<?php

namespace App\Controllers;

use App\Session;
use App\CSRF;
use App\Models\Seat;

class SeatController
{
    public function index(): void
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }

        $seatModel = new Seat();
        $seats = $seatModel->getAll();
        $csrfToken = CSRF::getToken();
        $username = Session::get('username');
        
        require __DIR__ . '/../../views/seats/index.php';
    }
}
