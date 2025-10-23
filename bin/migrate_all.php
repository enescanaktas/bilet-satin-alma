<?php
// Run all idempotent migrations in this bin/ folder
$migrations = [
    __DIR__ . '/migrate_add_passenger_gender.php',
    __DIR__ . '/migrate_add_users_firmid.php',
];
foreach ($migrations as $m) {
    if (!file_exists($m)) { echo "Skipping missing migration $m\n"; continue; }
    echo "Running $m\n";
    passthru("php " . escapeshellarg($m), $rc);
    if ($rc !== 0) { echo "Migration $m failed with code $rc\n"; exit($rc); }
}
echo "All migrations completed.\n";
