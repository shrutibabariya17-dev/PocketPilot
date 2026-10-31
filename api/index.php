<?php
// api/index.php - Vercel Serverless Front-Controller Router for PocketPilot

// 1. Resolve workspace root directory
$rootDir = dirname(__DIR__);

// 2. Parse the requested path
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$path = trim($path, '/');
$path = preg_replace('#^api/#i', '', $path);

// 3. Fallback static asset handling (for CSS, JS, images, icons if accessed via router)
if (preg_match('#\.(css|js|ico|png|jpg|jpeg|svg|webp|woff|woff2|ttf)$#i', $path)) {
    $staticFile = $rootDir . '/' . $path;
    if (file_exists($staticFile)) {
        $mimeMap = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'ico'   => 'image/x-icon',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf'
        ];
        $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($staticFile);
        exit;
    }
}

// 4. White-list allowed application pages
$routes = [
    ''                  => 'index.php',
    'index'             => 'index.php',
    'index.php'         => 'index.php',
    'dashboard'         => 'dashboard.php',
    'dashboard.php'     => 'dashboard.php',
    'add-expense'       => 'add-expense.php',
    'add-expense.php'   => 'add-expense.php',
    'income'            => 'income.php',
    'income.php'        => 'income.php',
    'transactions'      => 'transactions.php',
    'transactions.php'  => 'transactions.php',
    'budget'            => 'budget.php',
    'budget.php'        => 'budget.php',
    'savings'           => 'savings.php',
    'savings.php'       => 'savings.php',
    'reports'           => 'reports.php',
    'reports.php'       => 'reports.php',
    'tips'              => 'tips.php',
    'tips.php'          => 'tips.php',
    'about'             => 'about.php',
    'about.php'         => 'about.php',
    'login'             => 'login.php',
    'login.php'         => 'login.php',
    'logout'            => 'logout.php',
    'logout.php'        => 'logout.php',
    'register'          => 'register.php',
    'register.php'      => 'register.php',
];

// 5. Match route
$target = $routes[$path] ?? null;

// Allow direct access to existing root PHP file if safe
if (!$target) {
    $candidate = basename($path);
    if (!str_ends_with($candidate, '.php')) {
        $candidate .= '.php';
    }
    if (isset($routes[$candidate])) {
        $target = $routes[$candidate];
    }
}

// If route not found, serve 404 page
if (!$target || !file_exists($rootDir . '/' . $target)) {
    http_response_code(404);
    chdir($rootDir);
    $_SERVER['SCRIPT_NAME'] = '/404.php';
    $_SERVER['PHP_SELF'] = '/404.php';
    require $rootDir . '/404.php';
    exit;
}

// 6. Configure environment and execute requested page
chdir($rootDir);
$_SERVER['SCRIPT_NAME'] = '/' . $target;
$_SERVER['PHP_SELF'] = '/' . $target;

// Forward query parameters if any
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
    $_GET = array_merge($_GET, $queryParams);
}

require $rootDir . '/' . $target;
