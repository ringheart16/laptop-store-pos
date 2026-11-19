<?php
/**
 * Database Configuration
 * Laptop Store Management System
 * 
 * Supports:
 * 1. Environment variables (DATABASE_URL, MYSQL_URL, DB_HOST, DB_NAME, DB_USER, DB_PASS)
 * 2. Local XAMPP MySQL defaults (localhost:3306, laptop_store_db, root, empty password)
 * 3. Auto-migration / schema seed if tables are missing in target database
 * 4. Graceful SQLite fallback for zero-config Docker / Render deployments
 */

// Global DB mode indicator
$GLOBALS['DB_MODE'] = 'mysql';
$GLOBALS['DB_NAME_DISPLAY'] = 'laptop_store_db';

// Extract connection parameters from environment or defaults
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if (!empty($dbUrl)) {
    $parsed = parse_url($dbUrl);
    $db_host = $parsed['host'] ?? '127.0.0.1';
    $db_port = $parsed['port'] ?? 3306;
    $db_user = $parsed['user'] ?? 'root';
    $db_pass = $parsed['pass'] ?? '';
    $db_name = ltrim($parsed['path'] ?? '/laptop_store_db', '/');
} else {
    $db_host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1'));
    $db_port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: '3306'));
    $db_name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: 'laptop_store_db'));
    $db_user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: 'root'));
    $db_pass = getenv('DB_PASS');
    if ($db_pass === false) {
        $db_pass = getenv('DB_PASSWORD') ?: (getenv('MYSQLPASSWORD') ?: (getenv('MYSQL_PASSWORD') ?: ''));
    }
}

$GLOBALS['DB_NAME_DISPLAY'] = $db_name;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 3,
];

$pdo = null;

// 1. Attempt MySQL connection
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $pdo->exec("SET NAMES utf8mb4");
    $GLOBALS['DB_MODE'] = 'mysql';

    // Check if tables exist, auto-seed if empty cloud database
    try {
        $check = $pdo->query("SELECT 1 FROM products LIMIT 1");
    } catch (Exception $noTable) {
        autoSeedMySqlDatabase($pdo);
    }

} catch (Exception $mysqlError) {
    // 2. Fallback to SQLite (e.g. for Render preview or offline environments)
    $sqliteDir = __DIR__ . '/../../database';
    if (!is_dir($sqliteDir)) {
        @mkdir($sqliteDir, 0777, true);
    }
    $sqlitePath = $sqliteDir . '/laptop_store.sqlite';

    try {
        $pdo = new PDO("sqlite:{$sqlitePath}", null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Register custom SQL functions in SQLite so MySQL queries run seamlessly
        $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'));
        $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));

        $GLOBALS['DB_MODE'] = 'sqlite';
        $GLOBALS['DB_NAME_DISPLAY'] = 'SQLite (Cloud Mode)';

        // Auto-seed SQLite database if empty
        try {
            $check = $pdo->query("SELECT 1 FROM products LIMIT 1");
        } catch (Exception $noTable) {
            autoSeedSqliteDatabase($pdo);
        }

    } catch (Exception $sqliteError) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>Database Connection Failed</h1>";
        echo "<p>MySQL Error: " . htmlspecialchars($mysqlError->getMessage()) . "</p>";
        echo "<p>SQLite Fallback Error: " . htmlspecialchars($sqliteError->getMessage()) . "</p>";
        exit;
    }
}

/**
 * Auto-seed MySQL target database if products table is absent
 */
function autoSeedMySqlDatabase(PDO $db) {
    $seedFile = __DIR__ . '/../../database/seed_data.sql';
    if (!file_exists($seedFile)) {
        $seedFile = __DIR__ . '/../../database/setup.sql';
    }

    if (file_exists($seedFile)) {
        $sql = file_get_contents($seedFile);
        // Remove CREATE DATABASE and USE statements to preserve target database name
        $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
        $sql = preg_replace('/USE `?[a-zA-Z0-9_]+`?;/i', '', $sql);
        try {
            $db->exec($sql);
        } catch (Exception $e) {
            error_log("Auto-seed MySQL warning: " . $e->getMessage());
        }
    }
}

/**
 * Auto-seed SQLite target database
 */
function autoSeedSqliteDatabase(PDO $db) {
    $schema = "
        CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'Cashier'
        );
        CREATE TABLE IF NOT EXISTS customers (
            customer_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            email TEXT
        );
        CREATE TABLE IF NOT EXISTS products (
            product_id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_name TEXT NOT NULL,
            brand TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0.0,
            stock INTEGER NOT NULL DEFAULT 0,
            category TEXT NOT NULL DEFAULT 'Laptop',
            specifications TEXT
        );
        CREATE TABLE IF NOT EXISTS suppliers (
            supplier_id INTEGER PRIMARY KEY AUTOINCREMENT,
            supplier_name TEXT NOT NULL,
            phone TEXT
        );
        CREATE TABLE IF NOT EXISTS inventory (
            inventory_id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            stock INTEGER NOT NULL DEFAULT 0,
            last_updated TEXT
        );
        CREATE TABLE IF NOT EXISTS sales (
            sale_id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            total_amount REAL NOT NULL DEFAULT 0.0,
            discount_amount REAL NOT NULL DEFAULT 0.0,
            payment_method TEXT NOT NULL DEFAULT 'Cash',
            payment_status TEXT NOT NULL DEFAULT 'Paid',
            payment_reference TEXT,
            order_type TEXT NOT NULL DEFAULT 'pos',
            order_status TEXT NOT NULL DEFAULT 'Delivered',
            delivery_address TEXT,
            serial_number TEXT,
            warranty_info TEXT NOT NULL DEFAULT '1 Year Official Manufacturer Warranty',
            sale_date TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    ";

    $db->exec($schema);

    // Seed default staff accounts
    $adminPw = password_hash('password', PASSWORD_DEFAULT);
    $cashierPw = password_hash('12345', PASSWORD_DEFAULT);

    $db->exec("
        INSERT OR IGNORE INTO users (name, username, password, role) VALUES
        ('System Admin', 'admin', '{$adminPw}', 'Admin'),
        ('Store Manager', 'manager', '{$adminPw}', 'Manager'),
        ('Maria Cashier', 'maria', '{$cashierPw}', 'Cashier');

        INSERT OR IGNORE INTO customers (name, phone, email) VALUES
        ('Juan Dela Cruz', '09171234567', 'juan@email.com'),
        ('Maria Santos', '09181234567', 'maria@email.com'),
        ('Pedro Reyes', '09191234567', 'pedro@email.com'),
        ('Ana Garcia', '09201234567', 'ana@email.com');

        INSERT OR IGNORE INTO products (product_name, brand, price, stock, category, specifications) VALUES
        ('ROG Zephyrus G14', 'ASUS', 89999.00, 8, 'Gaming', 'AMD Ryzen 9, 16GB RAM, 1TB SSD, RTX 4060'),
        ('MacBook Air M2', 'Apple', 64999.00, 12, 'Ultrabook', 'Apple M2 Chip, 8GB RAM, 256GB SSD, Retina'),
        ('ThinkPad X1 Carbon Gen 11', 'Lenovo', 94999.00, 5, 'Business', 'Intel Core i7-1365U, 16GB RAM, 512GB SSD'),
        ('ZenBook 14 OLED', 'ASUS', 54999.00, 10, 'Ultrabook', 'Intel Core i5-13500H, 16GB RAM, 512GB SSD'),
        ('Predator Helios 16', 'Acer', 99999.00, 4, 'Gaming', 'Intel Core i9-13900HX, 32GB RAM, 1TB SSD, RTX 4070'),
        ('IdeaPad Slim 3', 'Lenovo', 29999.00, 15, 'Student', 'AMD Ryzen 5, 8GB RAM, 512GB SSD'),
        ('Pavilion 15', 'HP', 37999.00, 14, 'All-Rounder', 'Intel Core i5-1235U, 8GB RAM, 512GB SSD'),
        ('Inspiron 15 3520', 'Dell', 32999.00, 9, 'Budget', 'Intel Core i3-1215U, 8GB RAM, 256GB SSD');

        INSERT OR IGNORE INTO suppliers (supplier_name, phone) VALUES
        ('TechSource Philippines', '02-8123-4567'),
        ('CompuTrade Hardware Corp', '02-8234-5678'),
        ('Digital Solutions Distribution', '02-8345-6789');

        INSERT OR IGNORE INTO inventory (product_id, stock, last_updated) VALUES
        (1, 8, '2026-11-19'),
        (2, 12, '2026-11-19'),
        (3, 5, '2026-11-19'),
        (4, 10, '2026-11-19'),
        (5, 4, '2026-11-19'),
        (6, 15, '2026-11-19'),
        (7, 14, '2026-11-19'),
        (8, 9, '2026-11-19');

        INSERT OR IGNORE INTO sales (customer_id, product_id, quantity, total_amount, payment_method, sale_date) VALUES
        (1, 1, 1, 89999.00, 'GCash', '2026-11-19'),
        (2, 2, 1, 64999.00, 'Cash', '2026-11-19'),
        (3, 4, 1, 54999.00, 'Maya', '2026-11-18'),
        (4, 6, 1, 29999.00, 'Credit Card', '2026-11-17');
    ");
}

/**
 * Send JSON response and exit
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Send error JSON response and exit
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Send success JSON response and exit
 */
function jsonSuccess($data = [], $message = 'Success') {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Get sanitized input from request body (JSON)
 */
function getJsonInput() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $input = json_decode($raw, true);
        if (is_array($input)) return $input;
    }
    return !empty($_POST) ? $_POST : [];
}

/**
 * Set CORS headers
 */
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!empty($origin)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
}

setCorsHeaders();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
