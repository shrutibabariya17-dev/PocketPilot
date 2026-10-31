<?php
// reports.php - Student Financial Reports & Visual Visualizations
$pageTitle = "Financial Reports";
require_once __DIR__ . '/includes/header.php';

$selectedMonth = sanitize($_GET['month'] ?? CURRENT_MONTH);

// Fetch Summary for Selected Month
$summary = get_financial_summary($pdo, $selectedMonth);
$categorySpending = get_category_spending($pdo, $selectedMonth);

// Calculate Savings Rate
$savingsRate = $summary['total_income'] > 0 
    ? round((($summary['total_income'] - $summary['total_expenses']) / $summary['total_income']) * 100, 1) 
    : 0;

// Top Expense Category
$topExpenseCat = !empty($categorySpending) ? $categorySpending[0]['category'] : 'None';
$topExpenseAmt = !empty($categorySpending) ? (float)$categorySpending[0]['total'] : 0;

// Monthly Comparisons: Fetch last 6 months data for comparative bar chart
$stmtMonthly = $pdo->query("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month_key,
        DATE_FORMAT(date, '%b %Y') as month_label,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
    FROM transactions
    WHERE date >= DATE_SUB(CURRENT_DATE, INTERVAL 5 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key ASC
");
$monthlyHistory = $stmtMonthly->fetchAll();

// Find max value for scaling CSS bar chart
$maxBarVal = 100;
foreach ($monthlyHistory as $m) {
    if ((float)$m['total_income'] > $maxBarVal) $maxBarVal = (float)$m['total_income'];
    if ((float)$m['total_expense'] > $maxBarVal) $maxBarVal = (float)$m['total_expense'];
}

// Payment Method Distribution for Expenses
$stmtMethods = $pdo->prepare("
    SELECT payment_method, SUM(amount) as total, COUNT(*) as count 
    FROM transactions 
    WHERE type = 'expense' AND DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY payment_method 
    ORDER BY total DESC
");
$stmtMethods->execute([$selectedMonth]);
$paymentMethods = $stmtMethods->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Financial Reports & Insights</h1>
            <p class="page-subtitle">Visual summaries, category breakdowns, and monthly trends</p>
        </div>
        <div class="header-actions">
            <form action="reports.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="month" name="month" class="form-control" style="padding: 0.45rem 0.75rem;" 
                       value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            </form>
            <button onclick="window.print()" class="btn btn-outline btn-sm">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>

    <!-- REPORT KEY METRICS -->
    <div class="stats-grid">
        <div class="stat-card card-income">
            <div class="stat-info">
                <span class="stat-label">Net Student Cashflow</span>
                <span class="stat-value" style="color: <?= $summary['month_balance'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                    <?= $summary['month_balance'] >= 0 ? '+' : '' ?><?= format_currency($summary['month_balance']) ?>
                </span>
                <span class="stat-subtext">Income minus expenses (<?= date('M Y', strtotime($selectedMonth . '-01')) ?>)</span>
            </div>
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        </div>

        <div class="stat-card card-balance">
            <div class="stat-info">
                <span class="stat-label">Student Savings Rate</span>
                <span class="stat-value" style="color: var(--primary);"><?= $savingsRate ?>%</span>
                <span class="stat-subtext">
                    <?php if ($savingsRate >= 20): ?>
                        <i class="bi bi-star-fill text-warning"></i> Exceeds 20% savings rule!
                    <?php else: ?>
                        <i class="bi bi-info-circle"></i> Aim for at least 15-20%
                    <?php endif; ?>
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-percent"></i></div>
        </div>

        <div class="stat-card card-expense">
            <div class="stat-info">
                <span class="stat-label">Biggest Spend Category</span>
                <span class="stat-value" style="font-size: 1.35rem; color: var(--danger);"><?= htmlspecialchars($topExpenseCat) ?></span>
                <span class="stat-subtext"><?= format_currency($topExpenseAmt) ?> spent this month</span>
            </div>
            <div class="stat-icon"><i class="bi bi-tag-fill"></i></div>
        </div>
    </div>

    <!-- MONTHLY COMPARISON BAR CHART (CSS-BASED VISUALIZATION) -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div>
                <h2 class="card-title"><i class="bi bi-bar-chart-fill"></i> Monthly Comparison: Income vs. Expenses</h2>
                <p class="card-subtitle">Side-by-side performance trend over recent months</p>
            </div>
            <div style="display: flex; gap: 1rem; font-size: 0.85rem; font-weight: 600;">
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: var(--success); border-radius: 2px;"></span> Income
                </span>
                <span style="display: flex; align-items: center; gap: 0.35rem;">
                    <span style="display: inline-block; width: 12px; height: 12px; background: var(--danger); border-radius: 2px;"></span> Expenses
                </span>
            </div>
        </div>

        <?php if (empty($monthlyHistory)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 3rem 0;">No comparative monthly data available yet.</p>
        <?php else: ?>
            <div class="chart-container">
                <div class="css-bar-chart">
                    <?php foreach ($monthlyHistory as $hist): 
                        $incH = max(4, round(((float)$hist['total_income'] / $maxBarVal) * 180));
                        $expH = max(4, round(((float)$hist['total_expense'] / $maxBarVal) * 180));
                    ?>
                    <div class="chart-bar-group">
                        <div class="bars-wrapper">
                            <!-- Income Bar -->
                            <div class="chart-bar bar-income" style="height: <?= $incH ?>px;" 
                                 data-val="Inc: <?= format_currency($hist['total_income']) ?>"
                                 title="Income: <?= format_currency($hist['total_income']) ?>"></div>
                            <!-- Expense Bar -->
                            <div class="chart-bar bar-expense" style="height: <?= $expH ?>px;" 
                                 data-val="Exp: <?= format_currency($hist['total_expense']) ?>"
                                 title="Expense: <?= format_currency($hist['total_expense']) ?>"></div>
                        </div>
                        <span class="bar-label"><?= htmlspecialchars($hist['month_label']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2 COLUMN BREAKDOWNS: CATEGORY SPENDING & PAYMENT METHODS -->
    <div class="two-col-layout">
        
        <!-- Category-Wise Spending Breakdown -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><i class="bi bi-pie-chart-fill"></i> Category-Wise Breakdown</h2>
                    <p class="card-subtitle"><?= date('F Y', strtotime($selectedMonth . '-01')) ?> expense allocations</p>
                </div>
                <span class="badge badge-category"><?= format_currency($summary['total_expenses']) ?> total</span>
            </div>

            <?php if (empty($categorySpending)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2.5rem 0;">No expenses recorded for this month.</p>
            <?php else: ?>
                <div class="breakdown-list">
                    <?php 
                        $monthExpTotal = max(1, $summary['total_expenses']);
                        $palette = ['#ef4444', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#10b981', '#06b6d4', '#84cc16'];
                        $idx = 0;
                        foreach ($categorySpending as $c): 
                            $pct = round(($c['total'] / $monthExpTotal) * 100, 1);
                            $color = $palette[$idx % count($palette)];
                            $idx++;
                    ?>
                    <div class="breakdown-row">
                        <div class="breakdown-info">
                            <span style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: <?= $color ?>;"></span>
                                <?= htmlspecialchars($c['category']) ?>
                            </span>
                            <span><?= format_currency($c['total']) ?> <small style="color: var(--text-muted); font-weight: normal;">(<?= $pct ?>%)</small></span>
                        </div>
                        <div class="progress-bar-container" style="height: 10px;">
                            <div class="progress-bar" style="width: <?= $pct ?>%; background-color: <?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title"><i class="bi bi-credit-card"></i> Payment Methods</h3>
                    <p class="card-subtitle">How you paid for expenses</p>
                </div>
            </div>

            <?php if (empty($paymentMethods)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No expense records available.</p>
            <?php else: ?>
                <div class="breakdown-list">
                    <?php 
                        $payTotal = max(1, $summary['total_expenses']);
                        foreach ($paymentMethods as $pm): 
                            $pmPct = round(($pm['total'] / $payTotal) * 100, 1);
                    ?>
                    <div class="breakdown-row">
                        <div class="breakdown-info">
                            <span><?= htmlspecialchars($pm['payment_method']) ?></span>
                            <span><?= format_currency($pm['total']) ?> (<?= $pmPct ?>%)</span>
                        </div>
                        <div class="progress-bar-container" style="height: 8px;">
                            <div class="progress-bar progress-primary" style="width: <?= $pmPct ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1.5rem; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Student Insight:</strong> Paying with UPI or cards makes tracking automatic, but using physical cash for daily leisure can physically curb impulsive spending!
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

