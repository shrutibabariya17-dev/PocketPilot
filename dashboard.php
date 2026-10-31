<?php
// dashboard.php - PocketPilot Main Dashboard
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = "Student Dashboard";
require_once __DIR__ . '/includes/header.php';

$currentMonth = CURRENT_MONTH;
$summary = get_financial_summary($pdo, $currentMonth);
$recentTransactions = get_recent_transactions($pdo, 6);
$categorySpending = get_category_spending($pdo, $currentMonth);
$budgetStatus = get_budget_status($pdo, $currentMonth);
$currentUser = current_user();

// Calculate days remaining in month for daily spending pace
$daysInMonth = (int)date('t');
$currentDay = (int)date('j');
$daysRemaining = max(1, $daysInMonth - $currentDay + 1);
$avgDailySpend = $currentDay > 0 ? ($summary['total_expenses'] / $currentDay) : 0;
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <?php 
            $displayName = $currentUser['name'] ?? 'Shruti';
            if (stripos($displayName, 'Rahul') !== false) {
                $displayName = 'Shruti';
            }
            ?>
            <h1 class="page-title">Welcome back, <?= htmlspecialchars($displayName) ?>! 🎓</h1>
            <p class="page-subtitle">Here is your financial snapshot for <strong><?= date('F Y') ?></strong></p>
        </div>
        <div class="header-actions">
            <a href="add-expense.php" class="btn btn-danger">
                <i class="bi bi-dash-circle"></i> Add Expense
            </a>
            <a href="income.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Income
            </a>
        </div>
    </div>

    <!-- Active Budget Warnings (if any category >= 80% or 100%) -->
    <?php if (!empty($budgetStatus['warnings'])): ?>
    <div class="alert alert-warning">
        <div class="alert-content">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <div>
                <strong>Budget Alert:</strong>
                <ul style="margin: 0.35rem 0 0 1.25rem; font-size: 0.88rem;">
                    <?php foreach ($budgetStatus['warnings'] as $warning): ?>
                        <li><?= $warning ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <a href="budget.php" class="btn btn-outline btn-xs" style="border-color: #d97706; color: #92400e;">Manage Budgets &rarr;</a>
    </div>
    <?php endif; ?>

    <!-- 4 MAIN STAT CARDS -->
    <div class="stats-grid">
        <!-- Available Balance -->
        <div class="stat-card card-balance">
            <div class="stat-info">
                <span class="stat-label">Available Balance</span>
                <span class="stat-value" style="<?= $summary['available_balance'] < 0 ? 'color: var(--danger);' : '' ?>">
                    <?= format_currency($summary['available_balance']) ?>
                </span>
                <span class="stat-subtext">
                    <?php if ($summary['available_balance'] >= 100): ?>
                        <i class="bi bi-check-circle text-success"></i> Healthy student funds
                    <?php elseif ($summary['available_balance'] >= 0): ?>
                        <i class="bi bi-exclamation-circle text-warning"></i> Running low on reserve
                    <?php else: ?>
                        <i class="bi bi-x-circle text-danger"></i> Deficit: expenses exceed income
                    <?php endif; ?>
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        </div>

        <!-- Total Income (This Month) -->
        <div class="stat-card card-income">
            <div class="stat-info">
                <span class="stat-label">This Month's Income</span>
                <span class="stat-value" style="color: var(--success);"><?= format_currency($summary['total_income']) ?></span>
                <span class="stat-subtext">
                    <i class="bi bi-arrow-down-left"></i> Pocket money, work & grants
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        </div>

        <!-- Total Expenses (This Month) -->
        <div class="stat-card card-expense">
            <div class="stat-info">
                <span class="stat-label">This Month's Spend</span>
                <span class="stat-value" style="color: var(--danger);"><?= format_currency($summary['total_expenses']) ?></span>
                <span class="stat-subtext">
                    <i class="bi bi-receipt"></i> Avg <?= format_currency($avgDailySpend) ?> / day
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-credit-card-2-front"></i></div>
        </div>

        <!-- Budget Utilization -->
        <div class="stat-card card-budget">
            <div class="stat-info">
                <span class="stat-label">Budget Planned</span>
                <span class="stat-value">
                    <?php 
                        $totalLimit = $budgetStatus['total_budget'];
                        $totalSpentOnB = $budgetStatus['total_spent'];
                        $overallPct = $totalLimit > 0 ? round(($totalSpentOnB / $totalLimit) * 100, 1) : 0;
                    ?>
                    <?= $overallPct ?>%
                </span>
                <span class="stat-subtext">
                    <?= format_currency($totalSpentOnB) ?> of <?= format_currency($totalLimit) ?> used
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-pie-chart"></i></div>
        </div>
    </div>

    <!-- 2-COLUMN DASHBOARD GRID -->
    <div class="two-col-layout">
        
        <!-- Left Column: Recent Transactions Table -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><i class="bi bi-clock-history"></i> Recent Transactions</h2>
                    <p class="card-subtitle">Your latest logged expenses and income deposits</p>
                </div>
                <a href="transactions.php" class="btn btn-outline btn-xs">View All &rarr;</a>
            </div>

            <?php if (empty($recentTransactions)): ?>
                <div style="text-align: center; padding: 2.5rem 1rem;">
                    <i class="bi bi-inbox" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 0.5rem; display: block;"></i>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">No transactions recorded yet.</p>
                    <a href="add-expense.php" class="btn btn-primary btn-sm" style="margin-top: 1rem;">Record First Expense</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="width: 110px; white-space: nowrap;">Date</th>
                                <th>Description</th>
                                <th style="width: 170px; white-space: nowrap;">Category</th>
                                <th style="width: 120px; white-space: nowrap;">Method</th>
                                <th style="width: 140px; text-align: right; white-space: nowrap;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $tx): ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 0.85rem; color: var(--text-muted);">
                                    <?= date('M d, Y', strtotime($tx['date'])) ?>
                                </td>
                                <td>
                                    <strong style="color: var(--text-dark); display: block; font-size: 0.92rem;"><?= htmlspecialchars($tx['description']) ?></strong>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span class="badge badge-category">
                                        <?= htmlspecialchars($tx['category']) ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($tx['payment_method']) ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($tx['type'] === 'income'): ?>
                                        <span class="amount-income">+<?= format_currency($tx['amount']) ?></span>
                                    <?php else: ?>
                                        <span class="amount-expense">-<?= format_currency($tx['amount']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-light); padding-top: 1rem;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">Need to edit or delete transactions?</span>
                <a href="transactions.php" class="btn btn-outline-primary btn-xs">Manage in Transaction History</a>
            </div>
        </div>

        <!-- Right Column: Monthly Spending Summary Widget & Quick Insights -->
        <div>
            <!-- Monthly Spending Summary -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title"><i class="bi bi-pie-chart-fill"></i> Spending by Category</h2>
                        <p class="card-subtitle"><?= date('F Y') ?> category breakdown</p>
                    </div>
                    <a href="reports.php" class="btn btn-outline btn-xs">Reports</a>
                </div>

                <?php if (empty($categorySpending)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">
                        No expense records found for this month.
                    </p>
                <?php else: ?>
                    <div class="breakdown-list">
                        <?php 
                            $monthTotalExp = max(1, $summary['total_expenses']);
                            foreach ($categorySpending as $cat): 
                                $pct = round(($cat['total'] / $monthTotalExp) * 100, 1);
                                $barColor = !empty($cat['color']) ? $cat['color'] : '#4f46e5';
                                $catIcon = !empty($cat['icon']) ? $cat['icon'] : 'bi-tag';
                        ?>
                        <div class="breakdown-row" style="margin-bottom: 0.85rem;">
                            <div class="breakdown-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; font-size: 0.88rem;">
                                <span style="display: inline-flex; align-items: center; gap: 0.45rem; font-weight: 600; color: var(--text-dark);">
                                    <i class="bi <?= htmlspecialchars($catIcon) ?>" style="color: <?= htmlspecialchars($barColor) ?>; font-size: 0.95rem;"></i>
                                    <?= htmlspecialchars($cat['category']) ?>
                                </span>
                                <span style="white-space: nowrap; font-variant-numeric: tabular-nums; font-size: 0.88rem;">
                                    <strong><?= format_currency($cat['total']) ?></strong> 
                                    <small style="color: var(--text-muted); font-weight: normal;">(<?= $pct ?>%)</small>
                                </span>
                            </div>
                            <div class="progress-bar-container" style="height: 7px; background-color: #f1f5f9; border-radius: 9999px; overflow: hidden;">
                                <div class="progress-bar" style="width: <?= $pct ?>%; background-color: <?= htmlspecialchars($barColor) ?>; border-radius: 9999px; transition: width 0.6s ease;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Money Status Widget -->
            <div class="card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.75rem;">
                    <i class="bi bi-lightning-charge-fill" style="color: var(--warning);"></i> Student Money Health
                </h3>
                <p style="font-size: 0.88rem; color: var(--text-body); margin-bottom: 0.75rem;">
                    <?php if ($summary['month_balance'] >= 0): ?>
                        🎉 Great job! You are currently running a <strong>surplus of <?= format_currency($summary['month_balance']) ?></strong> this month.
                    <?php else: ?>
                        ⚠️ Heads up: You have spent <strong><?= format_currency(abs($summary['month_balance'])) ?></strong> more than you received this month.
                    <?php endif; ?>
                </p>
                <div style="font-size: 0.82rem; color: var(--text-muted); background: #f1f5f9; padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1rem;">
                    <strong>Pacing Tip:</strong> With <?= $daysRemaining ?> days left in <?= date('M') ?>, aim to keep your daily non-essential spend under <strong><?= format_currency(max(0, $summary['available_balance']) / $daysRemaining) ?></strong>.
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="savings.php" class="btn btn-outline btn-sm" style="flex: 1;">Check Savings</a>
                    <a href="tips.php" class="btn btn-outline btn-sm" style="flex: 1;">Student Tips</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

