<?php

namespace App\Models;

use App\Database;
use PDO;

class Seat
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT s.*, b.passenger_gender 
            FROM seats s
            LEFT JOIN bookings b ON s.id = b.seat_id
            ORDER BY s.seat_number
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM seats WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function markAsBooked(int $seatId): void
    {
        $stmt = $this->db->prepare("UPDATE seats SET is_available = 0 WHERE id = ?");
        $stmt->execute([$seatId]);
    }
}
