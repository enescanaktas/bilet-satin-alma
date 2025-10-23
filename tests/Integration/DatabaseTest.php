<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Database;
use App\Models\User;
use App\Models\Seat;
use App\Models\Booking;

class DatabaseTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        // Create test database
        $testDbPath = __DIR__ . '/../../database/test_tickets.db';
        
        if (file_exists($testDbPath)) {
            unlink($testDbPath);
        }

        // Initialize test database
        $this->db = new \PDO('sqlite:' . $testDbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create tables
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            CREATE TABLE seats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                seat_number VARCHAR(10) NOT NULL UNIQUE,
                is_available BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            CREATE TABLE bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                seat_id INTEGER NOT NULL,
                passenger_name VARCHAR(255) NOT NULL,
                passenger_gender VARCHAR(10) NOT NULL,
                user_id INTEGER,
                booking_code VARCHAR(20) NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (seat_id) REFERENCES seats(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");

        // Insert test data
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $this->db->exec("INSERT INTO users (username, password) VALUES ('testuser', '{$hashedPassword}')");
        $this->db->exec("INSERT INTO seats (seat_number) VALUES ('A1'), ('A2'), ('A3')");
    }

    protected function tearDown(): void
    {
        $this->db = null;
        $testDbPath = __DIR__ . '/../../database/test_tickets.db';
        
        if (file_exists($testDbPath)) {
            unlink($testDbPath);
        }
    }

    public function testUserAuthentication(): void
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['testuser']);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
        $this->assertTrue(password_verify('test123', $user['password']));
    }

    public function testSeatCreation(): void
    {
        $stmt = $this->db->query("SELECT * FROM seats WHERE seat_number = 'A1'");
        $seat = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotNull($seat);
        $this->assertEquals('A1', $seat['seat_number']);
        $this->assertEquals(1, $seat['is_available']);
    }

    public function testBookingCreation(): void
    {
        $bookingCode = 'TEST1234';
        $stmt = $this->db->prepare("
            INSERT INTO bookings (seat_id, passenger_name, passenger_gender, user_id, booking_code)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([1, 'John Doe', 'male', 1, $bookingCode]);

        $stmt = $this->db->prepare("
            SELECT b.*, s.seat_number
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            WHERE b.booking_code = ?
        ");
        $stmt->execute([$bookingCode]);
        $booking = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotNull($booking);
        $this->assertEquals('John Doe', $booking['passenger_name']);
        $this->assertEquals('male', $booking['passenger_gender']);
        $this->assertEquals('A1', $booking['seat_number']);
    }

    public function testSeatMarkAsBooked(): void
    {
        $this->db->exec("UPDATE seats SET is_available = 0 WHERE id = 1");
        
        $stmt = $this->db->query("SELECT * FROM seats WHERE id = 1");
        $seat = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals(0, $seat['is_available']);
    }
}
