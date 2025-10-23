<?php
// Allow overriding DB path via DATABASE_PATH env var for test isolation
$dbPath = getenv('DATABASE_PATH') ?: __DIR__ . '/../instance/eca.sqlite';
if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0755, true);
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// users: includes role info and credit balance
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user', -- values: user, firma_admin, admin
    firm_id INTEGER NULL,
    credit INTEGER DEFAULT 0,
    failed_attempts INTEGER DEFAULT 0,
    locked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// firms
$db->exec("CREATE TABLE IF NOT EXISTS firms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// trips (sefer)
$db->exec("CREATE TABLE IF NOT EXISTS trips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    firm_id INTEGER NOT NULL,
    departure TEXT NOT NULL,
    arrival TEXT NOT NULL,
    departure_time DATETIME NOT NULL,
    price INTEGER NOT NULL,
    total_seats INTEGER NOT NULL DEFAULT 40,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(firm_id) REFERENCES firms(id) ON DELETE CASCADE
)");

// seats: for explicit seat state if needed
$db->exec("CREATE TABLE IF NOT EXISTS seats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    seat_number INTEGER NOT NULL,
    UNIQUE(trip_id, seat_number),
    FOREIGN KEY(trip_id) REFERENCES trips(id) ON DELETE CASCADE
)");

// bookings / tickets
$db->exec("CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    trip_id INTEGER NOT NULL,
    seat_number INTEGER,
    price_paid INTEGER NOT NULL,
    passenger_gender TEXT DEFAULT '',
    coupon_code TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelled INTEGER DEFAULT 0,
    cancelled_at DATETIME,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(trip_id) REFERENCES trips(id) ON DELETE CASCADE
)");

// coupons
$db->exec("CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    percent INTEGER NOT NULL,
    usage_limit INTEGER DEFAULT 0,
    used_count INTEGER DEFAULT 0,
    expires_at DATETIME
)");

// transactions (credit movements)
$db->exec("CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount INTEGER NOT NULL,
    type TEXT NOT NULL, -- credit, debit, refund
    related_booking INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// throttles: per-action per-ip counters for simple rate-limiting
$db->exec("CREATE TABLE IF NOT EXISTS throttles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    ip TEXT NOT NULL,
    count INTEGER DEFAULT 0,
    until INTEGER DEFAULT 0,
    UNIQUE(action, ip)
)");

echo "DB initialized at $dbPath\n";

