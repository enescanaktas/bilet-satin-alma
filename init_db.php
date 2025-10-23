<?php
/**
 * Database initialization script
 * Creates the database and runs all migrations
 */

require_once __DIR__ . '/vendor/autoload.php';

$dbPath = __DIR__ . '/database/tickets.db';
$dbDir = dirname($dbPath);

// Create database directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Create database connection
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection established.\n";
    
    // Create migrations table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    echo "Migrations table ready.\n";
    
    // Get list of executed migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all migration files
    $migrationFiles = glob(__DIR__ . '/migrations/*.php');
    sort($migrationFiles);
    
    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);
        
        // Skip if already executed
        if (in_array($migrationName, $executedMigrations)) {
            echo "Skipping migration: $migrationName (already executed)\n";
            continue;
        }
        
        echo "Running migration: $migrationName\n";
        
        // Execute migration in isolated scope
        $migration = require $migrationFile;
        
        // Call the migration function if it returns a callable
        if (is_callable($migration)) {
            $migration($pdo);
            
            // Record migration
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationName]);
            
            echo "Migration completed: $migrationName\n";
        } else {
            echo "Warning: Migration $migrationName did not return a callable\n";
        }
    }
    
    echo "\nDatabase initialization complete!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
