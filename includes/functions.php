<?php
// includes/functions.php - Helper Functions for PocketPilot

/**
 * Sanitize user input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency string (handles negative numbers gracefully, e.g. -₹50.00)
 */
function format_currency($amount, $showSymbol = true) {
    $val = (float)$amount;
    $isNegative = $val < 0;
    $formatted = number_format(abs($val), 2);
    if ($showSymbol) {
        return ($isNegative ? '-' : '') . CURRENCY_SYMBOL . $formatted;
    }
    return ($isNegative ? '-' : '') . $formatted;
}

/**
 * Set a flash notification message
 */
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'text' => $message
    ];
}

/**
 * Get and clear flash notification message
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Active navigation link helper
 */
function is_active($pageName) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage === $pageName) ? 'active' : '';
}

/**
 * Get all categories by type ('expense' or 'income' or null for all)
 */
function get_categories($pdo, $type = null) {
    if ($type) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = ? ORDER BY name ASC");
        $stmt->execute([$type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY type ASC, name ASC");
    }
    return $stmt->fetchAll();
}

/**
 * Get overall financial summary
 */
function get_financial_summary($pdo, $month = null) {
    $whereExpense = "WHERE type = 'expense'";
    $whereIncome = "WHERE type = 'income'";
    $paramsExpense = [];
    $paramsIncome = [];

    if ($month) {
        $whereExpense .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
        $whereIncome .= " AND DATE_FORMAT(date, '%Y-%m') = ?";
        $paramsExpense[] = $month;
        $paramsIncome[] = $month;
    }

    // Total Expenses
    $stmtExp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions $whereExpense");
    $stmtExp->execute($paramsExpense);
    $totalExpenses = (float)$stmtExp->fetch()['total'];

    // Total Income
    $stmtInc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions $whereIncome");
    $stmtInc->execute($paramsIncome);
    $totalIncome = (float)$stmtInc->fetch()['total'];

    // Available Balance (All time total income - all time total expenses)
    $stmtAllExp = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'expense'");
    $allExpenses = (float)$stmtAllExp->fetch()['total'];

    $stmtAllInc = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'income'");
    $allIncome = (float)$stmtAllInc->fetch()['total'];

    $availableBalance = $allIncome - $allExpenses;

    return [
        'total_income' => $totalIncome,
        'total_expenses' => $totalExpenses,
        'available_balance' => $availableBalance,
        'month_balance' => $totalIncome - $totalExpenses,
        'all_income' => $allIncome,
        'all_expenses' => $allExpenses
    ];
}

/**
 * Get recent transactions
 */
function get_recent_transactions($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT * FROM transactions ORDER BY date DESC, id DESC LIMIT ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get category spending breakdown for given month
 */
function get_category_spending($pdo, $month) {
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) as total, COUNT(*) as count 
        FROM transactions 
        WHERE type = 'expense' AND DATE_FORMAT(date, '%Y-%m') = ?
        GROUP BY category 
        ORDER BY total DESC
    ");
    $stmt->execute([$month]);
    return $stmt->fetchAll();
}

/**
 * Get budget comparison data with spending progress and alerts
 */
function get_budget_status($pdo, $month) {
    // Fetch budgets
    $stmtB = $pdo->prepare("SELECT * FROM budgets WHERE month_year = ? ORDER BY category ASC");
    $stmtB->execute([$month]);
    $budgets = $stmtB->fetchAll();

    // Fetch spending per category for this month
    $stmtS = $pdo->prepare("
        SELECT category, SUM(amount) as spent 
        FROM transactions 
        WHERE type = 'expense' AND DATE_FORMAT(date, '%Y-%m') = ? 
        GROUP BY category
    ");
    $stmtS->execute([$month]);
    $spendingMap = [];
    foreach ($stmtS->fetchAll() as $row) {
        $spendingMap[$row['category']] = (float)$row['spent'];
    }

    $results = [];
    $totalBudget = 0;
    $totalSpentOnBudgeted = 0;
    $warnings = [];

    foreach ($budgets as $b) {
        $cat = $b['category'];
        $limit = (float)$b['monthly_limit'];
        $spent = $spendingMap[$cat] ?? 0.0;
        $remaining = $limit - $spent;
        $pct = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;

        $status = 'normal'; // 'normal', 'warning' (>=80%), 'danger' (>=100%)
        if ($pct >= 100) {
            $status = 'danger';
            $warnings[] = "You have exceeded your <strong>" . htmlspecialchars($cat) . "</strong> budget by " . format_currency(abs($remaining)) . "!";
        } elseif ($pct >= 80) {
            $status = 'warning';
            $warnings[] = "Warning: You have used {$pct}% of your <strong>" . htmlspecialchars($cat) . "</strong> budget.";
        }

        $results[] = [
            'id' => $b['id'],
            'category' => $cat,
            'limit' => $limit,
            'spent' => $spent,
            'remaining' => $remaining,
            'percentage' => $pct,
            'status' => $status
        ];

        $totalBudget += $limit;
        $totalSpentOnBudgeted += $spent;
    }

    $unbudgeted = [];
    $budgetedCats = array_column($budgets, 'category');
    foreach ($spendingMap as $cName => $cSpent) {
        if (!in_array($cName, $budgetedCats) && $cSpent > 0) {
            $unbudgeted[] = [
                'category' => $cName,
                'spent' => $cSpent
            ];
        }
    }

    return [
        'items' => $results,
        'total_budget' => $totalBudget,
        'total_spent' => $totalSpentOnBudgeted,
        'warnings' => $warnings,
        'unbudgeted' => $unbudgeted
    ];
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Retrieve current logged in user details
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Student',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

/**
 * Log in a user by saving their details to session
 */
function login_user($user) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
}

/**
 * Log out current user
 */
function logout_user() {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Require authentication for private pages
 */
function require_login($redirectUrl = null) {
    if (!is_logged_in()) {
        $target = $redirectUrl ?: basename($_SERVER['REQUEST_URI'] ?? 'dashboard.php');
        set_flash('info', 'Please sign in to access your finances. (You can use the 1-Click Demo Login!)');
        header("Location: login.php?redirect=" . urlencode($target));
        exit;
    }
}


