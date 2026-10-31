<?php
// includes/header.php - Global Header & Navigation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = isset($pageTitle) ? $pageTitle . " — " . APP_NAME : APP_NAME . " — " . APP_TAGLINE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- PocketPilot CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- TOP NAVIGATION BAR -->
    <header class="site-header">
        <div class="nav-container">
            <a href="index.php" class="brand-logo">
                <div class="logo-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">Pocket<strong>Pilot</strong></span>
                    <span class="brand-badge">Student Edition</span>
                </div>
            </a>

            <!-- Mobile Menu Toggle Button -->
            <button class="mobile-toggle" id="mobileMenuBtn" aria-label="Toggle Navigation">
                <i class="bi bi-list"></i>
            </button>

            <!-- Navigation Links (All 10 Pages) -->
            <nav class="nav-menu" id="navMenu">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?= is_active('index.php') ?>">
                            <i class="bi bi-house"></i> <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?= is_active('dashboard.php') ?>">
                            <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="add-expense.php" class="nav-link <?= is_active('add-expense.php') ?>">
                            <i class="bi bi-dash-circle"></i> <span>Add Expense</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="income.php" class="nav-link <?= is_active('income.php') ?>">
                            <i class="bi bi-plus-circle"></i> <span>Income</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="transactions.php" class="nav-link <?= is_active('transactions.php') ?>">
                            <i class="bi bi-clock-history"></i> <span>Transactions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="budget.php" class="nav-link <?= is_active('budget.php') ?>">
                            <i class="bi bi-pie-chart"></i> <span>Budget</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="savings.php" class="nav-link <?= is_active('savings.php') ?>">
                            <i class="bi bi-piggy-bank"></i> <span>Savings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link <?= is_active('reports.php') ?>">
                            <i class="bi bi-bar-chart-line"></i> <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="tips.php" class="nav-link <?= is_active('tips.php') ?>">
                            <i class="bi bi-lightbulb"></i> <span>Money Tips</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link <?= is_active('about.php') ?>">
                            <i class="bi bi-info-circle"></i> <span>About & Contact</span>
                        </a>
                    </li>
                </ul>

                <!-- Header User & Actions -->
                <div class="nav-actions">
                    <?php if (is_logged_in()): ?>
                        <?php $currentUser = current_user(); $firstName = explode(' ', $currentUser['name'])[0]; ?>
                        <div class="user-menu-wrapper" id="userMenuWrapper">
                            <button type="button" class="user-pill-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false" title="Account Menu">
                                <span class="user-avatar"><i class="bi bi-person-fill"></i></span>
                                <span class="user-name"><?= htmlspecialchars($firstName) ?></span>
                                <i class="bi bi-chevron-down user-caret"></i>
                            </button>
                            <div class="user-dropdown-menu" id="userDropdownMenu">
                                <div class="dropdown-user-info">
                                    <strong class="dropdown-name"><?= htmlspecialchars($currentUser['name']) ?></strong>
                                    <span class="dropdown-email"><?= htmlspecialchars($currentUser['email']) ?></span>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="dashboard.php" class="dropdown-link"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                                <a href="transactions.php" class="dropdown-link"><i class="bi bi-clock-history"></i> My Transactions</a>
                                <a href="budget.php" class="dropdown-link"><i class="bi bi-pie-chart"></i> Budgets</a>
                                <a href="savings.php" class="dropdown-link"><i class="bi bi-piggy-bank"></i> Savings Goals</a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-link dropdown-logout"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
                            </div>
                        </div>
                        <a href="add-expense.php" class="btn btn-primary btn-sm nav-cta">
                            <i class="bi bi-plus-lg"></i> <span>Expense</span>
                        </a>
                        <a href="logout.php" class="btn btn-outline btn-sm nav-logout-icon" title="Sign out of PocketPilot">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-sm nav-login">
                            <i class="bi bi-box-arrow-in-right"></i> <span>Log In</span>
                        </a>
                        <a href="register.php" class="btn btn-primary btn-sm nav-cta">
                            <i class="bi bi-person-plus"></i> <span>Sign Up</span>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Global Flash Notification -->
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="container flash-container">
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <div class="alert-content">
                <?php if ($flash['type'] === 'success'): ?>
                    <i class="bi bi-check-circle-fill alert-icon"></i>
                <?php elseif ($flash['type'] === 'danger'): ?>
                    <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <?php elseif ($flash['type'] === 'warning'): ?>
                    <i class="bi bi-exclamation-circle-fill alert-icon"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle-fill alert-icon"></i>
                <?php endif; ?>
                <div><?= $flash['text'] ?></div>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Page Content Starts -->
    <main class="main-content">

