<?php
require __DIR__ . '/../src/db.php';
$db = get_db();
try {
    $stmt = $db->query("PRAGMA table_info(users)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $found = false;
    foreach ($cols as $c) {
        if (isset($c['name']) && $c['name'] === 'firm_id') { $found = true; break; }
    }
    if ($found) { echo "firm_id column already exists in users\n"; exit(0); }
    $db->exec("ALTER TABLE users ADD COLUMN firm_id INTEGER NULL");
    echo "Added firm_id column to users\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
