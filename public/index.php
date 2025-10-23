<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\SeatController;
use App\Controllers\BookingController;

// Initialize router
$router = new Router();

// Auth routes
$router->get('/login', function() {
    $controller = new AuthController();
    $controller->showLogin();
});

$router->post('/login', function() {
    $controller = new AuthController();
    $controller->login();
});

$router->get('/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

// Seat routes
$router->get('/seats', function() {
    $controller = new SeatController();
    $controller->index();
});

// Booking routes
$router->post('/booking/create', function() {
    $controller = new BookingController();
    $controller->create();
});

$router->get('/booking/{code}', function($code) {
    $controller = new BookingController();
    $controller->show($code);
});

$router->get('/booking/{code}/pdf', function($code) {
    $controller = new BookingController();
    $controller->generatePDF($code);
});

// Home route - redirect to login
$router->get('/', function() {
    header('Location: /login');
    exit;
});

// Dispatch the request
$router->dispatch();
