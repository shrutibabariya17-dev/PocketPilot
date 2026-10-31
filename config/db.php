<?php
// config/db.php - PocketPilot Production Database Configuration & Auto-Installer

// Enable output buffering to prevent headers already sent issues
if (!ob_get_level()) {
    ob_start();
}

// Lightweight zero-dependency .env loader for deployment
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'");
            if (getenv($key) === false) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Production error reporting configuration
$appEnv = getenv('APP_ENV') ?: 'production';
if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Session security
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Ensure correct timezone (defaults to Asia/Kolkata or user configured)
$appTimezone = getenv('APP_TIMEZONE') ?: 'Asia/Kolkata';
date_default_timezone_set($appTimezone);

// 1. Resolve Database Credentials (supports Cloud DB URLs, Environment Variables & Local XAMPP fallback)
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: 3306;
$db_name = getenv('DB_NAME') ?: 'pocketpilot_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$db_charset = 'utf8mb4';

// Check if a single Cloud Database URL is provided (e.g., Railway, Heroku, Render)
$cloudDbUrl = getenv('DATABASE_URL') ?: (getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL'));
if ($cloudDbUrl) {
    $parsedUrl = parse_url($cloudDbUrl);
    if ($parsedUrl) {
        $db_host = $parsedUrl['host'] ?? $db_host;
        $db_port = $parsedUrl['port'] ?? 3306;
        $db_user = $parsedUrl['user'] ?? $db_user;
        $db_pass = $parsedUrl['pass'] ?? $db_pass;
        $db_name = ltrim($parsedUrl['path'] ?? '', '/') ?: $db_name;
    }
}

$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Attempt auto-creation if database doesn't exist (useful on local/VPS)
    try {
        $tempPdo = new PDO("mysql:host=$db_host;port=$db_port;charset=$db_charset", $db_user, $db_pass, $options);
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $ex) {
        // Safe production error display
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
        if ($isLocal) {
            die("Database Connection Error: " . htmlspecialchars($ex->getMessage()) . "<br>Please ensure MySQL is running in XAMPP / your database service.");
        } else {
            error_log("PocketPilot DB Error: " . $ex->getMessage());
            die("We are having trouble connecting to the database. Please check back shortly.");
        }
    }
}

// 2. Auto-initialize tables if empty (makes first deployment 100% zero-configuration)
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'transactions'")->fetch();
    if (!$checkTable) {
        $schemaFile = __DIR__ . '/../database.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
    }

    // Ensure users table exists for authentication
    $checkUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$checkUsers) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `avatar` VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
            (1, 'Rahul Sharma', 'student@pocketpilot.com', '$2y$10$HidVwGe4quVBHxSlr/eyCetdbINgLsRb6eQtEnv0iU2WPe/OYSCIm')
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `password` = VALUES(`password`);
        ");
    }
} catch (PDOException $ex) {
    // Non-fatal if tables already exist or permissions are restricted
    error_log("Schema auto-init check: " . $ex->getMessage());
}

// App-wide configuration constants
define('APP_NAME', 'PocketPilot');
define('APP_TAGLINE', 'Your Smart Student Financial Co-Pilot');
define('CURRENCY_SYMBOL', getenv('CURRENCY_SYMBOL') ?: '₹');
define('CURRENT_MONTH', date('Y-m'));
