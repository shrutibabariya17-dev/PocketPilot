<?php
// config/db.php - PocketPilot Production Database Configuration & Auto-Installer

// Enable output buffering with gzip compression to minimize page payload and network transfer time
if (!ob_get_level()) {
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        @ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
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

// Session security & Serverless compatibility
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL') || !empty($_ENV['VERCEL']) || (function_exists('is_dir') && is_dir('/tmp'))) {
        @ini_set('session.save_path', '/tmp');
    }
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

// Guarantee we never connect to restricted MySQL system schemas ('sys', 'mysql', etc.)
if (empty($db_name) || in_array(strtolower($db_name), ['sys', 'mysql', 'performance_schema', 'information_schema'])) {
    $db_name = 'test';
}

$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true, // High performance: eliminates extra server round-trips for prepared statements
    PDO::ATTR_TIMEOUT            => 4,    // Fast 4-second network timeout prevents hanging
];

// Automatically configure SSL for Cloud MySQL providers (TiDB Cloud, Aiven, Railway, etc.)
if ($db_host !== 'localhost' && $db_host !== '127.0.0.1') {
    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        } elseif (file_exists('/etc/pki/tls/certs/ca-bundle.crt')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/pki/tls/certs/ca-bundle.crt';
        }
    }
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

function get_real_pdo() {
    static $realPdo = null;
    if ($realPdo !== null) {
        return $realPdo;
    }

    global $dsn, $db_user, $db_pass, $options, $db_host, $db_port, $db_charset, $db_name;

    try {
        $realPdo = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        // Attempt auto-creation if database doesn't exist (useful on local/VPS)
        try {
            $tempPdo = new PDO("mysql:host=$db_host;port=$db_port;charset=$db_charset", $db_user, $db_pass, $options);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $realPdo = new PDO($dsn, $db_user, $db_pass, $options);
        } catch (PDOException $ex) {
        // Helpful error display for Local and Vercel deployments
        $isVercel = getenv('VERCEL') || !empty($_ENV['VERCEL']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'vercel.app') !== false;
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']) && !$isVercel;

        if ($isVercel) {
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Connect Database — PocketPilot on Vercel</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                <style>
                    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 2.5rem 1rem; display: flex; align-items: center; justify-content: center; min-height: 100vh; box-sizing: border-box; }
                    .setup-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; max-width: 620px; width: 100%; padding: 2.5rem 2rem; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08); }
                    .badge { display: inline-flex; align-items: center; gap: 0.4rem; background: #e0e7ff; color: #4338ca; padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.82rem; font-weight: 700; margin-bottom: 1rem; }
                    h1 { font-size: 1.6rem; font-weight: 800; margin: 0 0 0.5rem; color: #0f172a; }
                    p { font-size: 0.95rem; line-height: 1.6; color: #475569; margin: 0 0 1.25rem; }
                    .code-box { background: #0f172a; color: #f8fafc; padding: 1.25rem; border-radius: 10px; font-family: monospace; font-size: 0.88rem; line-height: 1.6; margin-bottom: 1.5rem; overflow-x: auto; }
                    .steps { list-style: none; padding: 0; margin: 0 0 1.5rem; }
                    .steps li { display: flex; gap: 0.75rem; margin-bottom: 0.85rem; font-size: 0.92rem; color: #334155; }
                    .step-num { width: 24px; height: 24px; border-radius: 50%; background: #4f46e5; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 700; flex-shrink: 0; }
                    .btn { display: inline-flex; align-items: center; gap: 0.5rem; background: #4f46e5; color: #ffffff; text-decoration: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; }
                    .btn:hover { background: #4338ca; }
                </style>
            </head>
            <body>
                <div class="setup-card">
                    <div class="badge"><i class="bi bi-triangle-fill" style="font-size: 0.7rem;"></i> PocketPilot Live on Vercel</div>
                    <h1>Connect Your MySQL Database</h1>
                    <p>PocketPilot is running on Vercel! To store student expenses and budgets, connect a free Cloud MySQL database (such as <strong>Aiven</strong>, <strong>TiDB Cloud</strong>, or <strong>Railway</strong>).</p>
                    
                    <ul class="steps">
                        <li><span class="step-num">1</span> <div>Create a free MySQL database on <a href="https://aiven.io" target="_blank" style="color: #4f46e5; font-weight: 600;">Aiven</a>, <a href="https://tidbcloud.com" target="_blank" style="color: #4f46e5; font-weight: 600;">TiDB Cloud</a>, or <a href="https://railway.com" target="_blank" style="color: #4f46e5; font-weight: 600;">Railway</a>.</div></li>
                        <li><span class="step-num">2</span> <div>Go to your <strong>Vercel Dashboard &rarr; Project Settings &rarr; Environment Variables</strong>.</div></li>
                        <li><span class="step-num">3</span> <div>Add these variables from your cloud database:</div></li>
                    </ul>

                    <div class="code-box">
DB_HOST = your-cloud-mysql-host<br>
DB_PORT = 3306<br>
DB_NAME = pocketpilot_db<br>
DB_USER = your-database-username<br>
DB_PASS = your-database-password<br>
# Or simply:<br>
DATABASE_URL = mysql://user:pass@host:3306/pocketpilot_db
                    </div>

                    <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 1.5rem;">
                        <i class="bi bi-magic" style="color: #4f46e5;"></i> PocketPilot will <strong>automatically create all tables and initial student data</strong> on your first visit!
                    </p>

                    <a href="https://vercel.com/dashboard" target="_blank" class="btn">
                        <i class="bi bi-box-arrow-up-right"></i> Open Vercel Project Settings
                    </a>
                </div>
            </body>
            </html>
            <?php
            exit;
        } elseif ($isLocal) {
            die("Database Connection Error: " . htmlspecialchars($ex->getMessage()) . "<br>Please ensure MySQL is running in XAMPP / your database service.");
        } else {
            error_log("PocketPilot DB Error: " . $ex->getMessage());
            die("We are having trouble connecting to the database. Please check back shortly.");
        }
    }
}

    // 2. High-Speed Schema & Demo Account Initialization (Cached in /tmp so zero queries run on warm requests)
    $schemaReadyLock = sys_get_temp_dir() . '/.pp_db_init_lock_v3';
    if (!file_exists($schemaReadyLock)) {
        try {
            $checkTable = $realPdo->query("SHOW TABLES LIKE 'transactions'")->fetch();
            if (!$checkTable) {
                $tables = [
                    "CREATE TABLE IF NOT EXISTS `categories` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(50) NOT NULL,
                        `type` ENUM('expense', 'income') NOT NULL,
                        `icon` VARCHAR(50) DEFAULT 'bi-tag',
                        `color` VARCHAR(20) DEFAULT '#4f46e5'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `transactions` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `type` ENUM('expense', 'income') NOT NULL,
                        `amount` DECIMAL(10,2) NOT NULL,
                        `category` VARCHAR(50) NOT NULL,
                        `date` DATE NOT NULL,
                        `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
                        `description` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `budgets` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `category` VARCHAR(50) NOT NULL,
                        `monthly_limit` DECIMAL(10,2) NOT NULL,
                        `month_year` VARCHAR(7) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY `cat_month` (`category`, `month_year`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `savings_goals` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `title` VARCHAR(100) NOT NULL,
                        `target_amount` DECIMAL(10,2) NOT NULL,
                        `current_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        `target_date` DATE NOT NULL,
                        `notes` TEXT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `savings_contributions` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `goal_id` INT NOT NULL,
                        `amount` DECIMAL(10,2) NOT NULL,
                        `contribution_date` DATE NOT NULL,
                        `note` VARCHAR(255) NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (`goal_id`) REFERENCES `savings_goals`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `contact_messages` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `email` VARCHAR(150) NOT NULL,
                        `subject` VARCHAR(150) NOT NULL,
                        `message` TEXT NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `email` VARCHAR(150) NOT NULL UNIQUE,
                        `password` VARCHAR(255) NOT NULL,
                        `avatar` VARCHAR(255) DEFAULT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
                ];

                foreach ($tables as $tSql) {
                    $realPdo->exec($tSql);
                }

                // Seed initial categories
                $realPdo->exec("INSERT INTO `categories` (`name`, `type`, `icon`, `color`) VALUES
                    ('Food & Dining', 'expense', 'bi-cup-hot', '#ef4444'),
                    ('Books & Study Supplies', 'expense', 'bi-book', '#3b82f6'),
                    ('Rent & Housing', 'expense', 'bi-house-door', '#8b5cf6'),
                    ('Transportation', 'expense', 'bi-bus-front', '#f59e0b'),
                    ('Entertainment & Outings', 'expense', 'bi-controller', '#ec4899'),
                    ('Personal Care & Health', 'expense', 'bi-heart-pulse', '#10b981'),
                    ('Tech & Subscriptions', 'expense', 'bi-laptop', '#06b6d4'),
                    ('Groceries', 'expense', 'bi-cart', '#84cc16'),
                    ('Other Expense', 'expense', 'bi-three-dots', '#64748b'),
                    ('Pocket Money / Allowance', 'income', 'bi-wallet2', '#10b981'),
                    ('Scholarship / Grant', 'income', 'bi-award', '#3b82f6'),
                    ('Part-Time Work', 'income', 'bi-briefcase', '#f59e0b'),
                    ('Freelance & Tutoring', 'income', 'bi-laptop', '#8b5cf6'),
                    ('Gifts & Cashbacks', 'income', 'bi-gift', '#ec4899'),
                    ('Other Income', 'income', 'bi-cash-coin', '#06b6d4');");

                // Seed initial student transactions
                $realPdo->exec("INSERT INTO `transactions` (`type`, `amount`, `category`, `date`, `payment_method`, `description`) VALUES
                    ('income', 15000.00, 'Scholarship / Grant', DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), 'Bank Transfer', 'Semester Merit Scholarship'),
                    ('income', 6000.00, 'Pocket Money / Allowance', DATE_FORMAT(CURRENT_DATE, '%Y-%m-03'), 'UPI / Online', 'Monthly Home Pocket Money'),
                    ('income', 3500.00, 'Part-Time Work', DATE_FORMAT(CURRENT_DATE, '%Y-%m-10'), 'Debit Card', 'Campus Library Shift Wages'),
                    ('income', 2500.00, 'Freelance & Tutoring', DATE_FORMAT(CURRENT_DATE, '%Y-%m-18'), 'UPI / Online', 'Math & Coding Tutoring Sessions'),
                    ('expense', 650.00, 'Books & Study Supplies', DATE_FORMAT(CURRENT_DATE, '%Y-%m-04'), 'Debit Card', 'Data Structures & Algorithms textbook'),
                    ('expense', 180.00, 'Food & Dining', DATE_FORMAT(CURRENT_DATE, '%Y-%m-05'), 'UPI / Online', 'Campus canteen lunch with study group'),
                    ('expense', 600.00, 'Transportation', DATE_FORMAT(CURRENT_DATE, '%Y-%m-06'), 'UPI / Online', 'Monthly student metro rail pass'),
                    ('expense', 1850.00, 'Groceries', DATE_FORMAT(CURRENT_DATE, '%Y-%m-08'), 'Credit Card', 'Hostel room pantry restocking'),
                    ('expense', 149.00, 'Tech & Subscriptions', DATE_FORMAT(CURRENT_DATE, '%Y-%m-12'), 'UPI / Online', 'Spotify Student + Cloud Storage'),
                    ('expense', 450.00, 'Food & Dining', DATE_FORMAT(CURRENT_DATE, '%Y-%m-15'), 'Cash', 'Dinner celebration after midterm exams'),
                    ('expense', 320.00, 'Personal Care & Health', DATE_FORMAT(CURRENT_DATE, '%Y-%m-19'), 'UPI / Online', 'Pharmacy prescriptions and vitamins'),
                    ('expense', 350.00, 'Entertainment & Outings', DATE_FORMAT(CURRENT_DATE, '%Y-%m-22'), 'UPI / Online', 'Weekend movie ticket and popcorn'),
                    ('expense', 1200.00, 'Groceries', DATE_FORMAT(CURRENT_DATE, '%Y-%m-25'), 'Debit Card', 'Weekly grocery supermarket trip'),
                    ('expense', 65.00, 'Food & Dining', CURRENT_DATE, 'Cash', 'Evening chai & samosa at campus cafe');");

                // Seed initial budgets
                $realPdo->exec("INSERT INTO `budgets` (`category`, `monthly_limit`, `month_year`) VALUES
                    ('Food & Dining', 3500.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
                    ('Groceries', 4000.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
                    ('Books & Study Supplies', 1500.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
                    ('Transportation', 1000.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
                    ('Entertainment & Outings', 1200.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
                    ('Tech & Subscriptions', 500.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
                    ('Personal Care & Health', 1000.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m'));");

                // Seed initial savings goals
                $realPdo->exec("INSERT INTO `savings_goals` (`title`, `target_amount`, `current_amount`, `target_date`, `notes`) VALUES
                    ('New Coding Laptop (MacBook Air)', 65000.00, 42000.00, DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY), 'For computer science courses and app development'),
                    ('Emergency Student Cushion', 10000.00, 7500.00, DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), '3 months of unexpected expenses safety net'),
                    ('Spring Semester Break Trip', 6000.00, 3200.00, DATE_ADD(CURRENT_DATE, INTERVAL 120 DAY), 'Road trip with college roommates'),
                    ('Certification Exam Fee', 3500.00, 3500.00, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 'AWS Certified Cloud Practitioner voucher - Fully Saved!');");
            }

            // Always ensure users table and student demo account exist
            $checkUsers = $realPdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$checkUsers) {
                $realPdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(150) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `avatar` VARCHAR(255) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // Ensure demo account exists and account holder name is Shruti
            $demoUser = $realPdo->query("SELECT id, name FROM `users` WHERE `email` = 'student@pocketpilot.com' LIMIT 1")->fetch();
            if (!$demoUser) {
                $realPdo->exec("INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
                    (1, 'Shruti', 'student@pocketpilot.com', '$2y$10$HidVwGe4quVBHxSlr/eyCetdbINgLsRb6eQtEnv0iU2WPe/OYSCIm')
                    ON DUPLICATE KEY UPDATE `name` = 'Shruti';");
            } else if ($demoUser['name'] !== 'Shruti') {
                $realPdo->exec("UPDATE `users` SET `name` = 'Shruti' WHERE `email` = 'student@pocketpilot.com' OR `id` = 1");
            }

            @touch($schemaReadyLock);
        } catch (Throwable $ex) {
            error_log("Schema auto-init check: " . $ex->getMessage());
        }
    }

    return $realPdo;
}

/**
 * High-Performance Lazy PDO Proxy:
 * Defers remote database TLS handshakes until a SQL statement is actually needed.
 */
class LazyPDO {
    public function query(...$args) {
        return get_real_pdo()->query(...$args);
    }
    public function prepare(...$args) {
        return get_real_pdo()->prepare(...$args);
    }
    public function exec(...$args) {
        return get_real_pdo()->exec(...$args);
    }
    public function lastInsertId(...$args) {
        return get_real_pdo()->lastInsertId(...$args);
    }
    public function beginTransaction() {
        return get_real_pdo()->beginTransaction();
    }
    public function commit() {
        return get_real_pdo()->commit();
    }
    public function rollBack() {
        return get_real_pdo()->rollBack();
    }
    public function inTransaction() {
        return get_real_pdo()->inTransaction();
    }
    public function getAttribute($attr) {
        return get_real_pdo()->getAttribute($attr);
    }
    public function setAttribute($attr, $val) {
        return get_real_pdo()->setAttribute($attr, $val);
    }
    public function __call($name, $args) {
        return call_user_func_array([get_real_pdo(), $name], $args);
    }
}

$pdo = new LazyPDO();

// App-wide configuration constants
define('APP_NAME', 'PocketPilot');
define('APP_TAGLINE', 'Your Smart Student Financial Co-Pilot');
define('CURRENCY_SYMBOL', '₹');
define('CURRENT_MONTH', date('Y-m'));
