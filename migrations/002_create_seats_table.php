<?php
/**
 * Migration: Create seats table
 */

return function($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS seats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seat_number VARCHAR(10) NOT NULL UNIQUE,
            is_available BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create 30 seats (A1-A10, B1-B10, C1-C10)
    $rows = ['A', 'B', 'C'];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO seats (seat_number) VALUES (?)");
    
    foreach ($rows as $row) {
        for ($i = 1; $i <= 10; $i++) {
            $seatNumber = $row . $i;
            $stmt->execute([$seatNumber]);
        }
    }
    
    echo "  - Seats table created with 30 seats\n";
};
