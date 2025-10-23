<?php
/**
 * Migration: Create bookings table
 */

return function($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
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
    
    echo "  - Bookings table created\n";
};
