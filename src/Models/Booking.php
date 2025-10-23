<?php

namespace App\Models;

use App\Database;
use PDO;

class Booking
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $seatId, string $passengerName, string $passengerGender, int $userId): string
    {
        $bookingCode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        
        $stmt = $this->db->prepare("
            INSERT INTO bookings (seat_id, passenger_name, passenger_gender, user_id, booking_code)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$seatId, $passengerName, $passengerGender, $userId, $bookingCode]);
        
        return $bookingCode;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, s.seat_number, u.username
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.booking_code = ?
        ");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT b.*, s.seat_number
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            ORDER BY b.created_at DESC
        ");
        return $stmt->fetchAll();
    }
}
