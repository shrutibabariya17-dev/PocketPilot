<?php
// budget.php - Student Budget Planner & Overspending Warnings
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$selectedMonth = sanitize($_GET['month'] ?? CURRENT_MONTH);
$errors = [];

// Handle Set or Update Budget Form BEFORE sending HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_budget') {
    $bCategory = sanitize($_POST['category'] ?? '');
    $bLimit = filter_input(INPUT_POST, 'monthly_limit', FILTER_VALIDATE_FLOAT);
    $bMonth = sanitize($_POST['month_year'] ?? CURRENT_MONTH);

    if (empty($bCategory)) {
        $errors[] = "Please select a category.";
    }
    if ($bLimit === false || $bLimit <= 0) {
        $errors[] = "Please enter a valid monthly budget limit greater than 0.";
    }
    if (empty($bMonth) || !preg_match('/^\d{4}-\d{2}$/', $bMonth)) {
        $errors[] = "Invalid month format.";
    }

    if (empty($errors)) {
        try {
            $stmtSave = $pdo->prepare("
                INSERT INTO budgets (category, monthly_limit, month_year)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit)
            ");
            $stmtSave->execute([$bCategory, $bLimit, $bMonth]);

            set_flash('success', "Budget limit of <strong>" . format_currency($bLimit) . "</strong> set for '{$bCategory}' ({$bMonth})!");
            header("Location: budget.php?month=" . urlencode($bMonth));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Budget Target
if (isset($_GET['delete'])) {
    $delBudgetId = (int)$_GET['delete'];
    try {
        $stmtDelB = $pdo->prepare("DELETE FROM budgets WHERE id = ?");
        $stmtDelB->execute([$delBudgetId]);
        set_flash('success', "Budget target removed successfully.");
        header("Location: budget.php?month=" . urlencode($selectedMonth));
        exit;
    } catch (PDOException $e) {
        set_flash('danger', "Error deleting budget: " . $e->getMessage());
    }
}

// Fetch budget comparison & alerts for selected month
$budgetData = get_budget_status($pdo, $selectedMonth);
$expenseCategories = get_categories($pdo, 'expense');

// Calculate days remaining in selected month for daily pacing
$monthParts = explode('-', $selectedMonth);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$monthParts[1], (int)$monthParts[0]);
$isCurrentMonth = ($selectedMonth === CURRENT_MONTH);
$currentDay = $isCurrentMonth ? (int)date('j') : 1;
$daysLeft = $isCurrentMonth ? max(1, $daysInMonth - $currentDay + 1) : $daysInMonth;

$totalRemaining = max(0, $budgetData['total_budget'] - $budgetData['total_spent']);
$dailySpendLimit = $daysLeft > 0 ? ($totalRemaining / $daysLeft) : 0;

// HTML Layout Starts Here
$pageTitle = "Budget Planner";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Budget Planner</h1>
            <p class="page-subtitle">Set monthly caps, monitor real-time spending progress, and prevent month-end crunches</p>
        </div>
        <div class="header-actions">
            <!-- Month Selector Form -->
            <form action="budget.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="month" name="month" class="form-control" style="padding: 0.45rem 0.75rem;" 
                       value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
                <button type="submit" class="btn btn-outline btn-sm">Switch Month</button>
            </form>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div class="alert-content">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div>
                    <strong>Please fix these errors:</strong>
                    <ul style="margin: 0.35rem 0 0 1.25rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- OVERSPENDING WARNING BANNERS -->
    <?php if (!empty($budgetData['warnings'])): ?>
    <div class="alert alert-danger" style="margin-bottom: 2rem;">
        <div class="alert-content">
            <i class="bi bi-exclamation-octagon-fill alert-icon" style="font-size: 1.5rem;"></i>
            <div>
                <h4 style="font-weight: 700; margin-bottom: 0.25rem;">Budget Threshold Alerts (<?= date('F Y', strtotime($selectedMonth . '-01')) ?>)</h4>
                <ul style="margin: 0.35rem 0 0 1.25rem; font-size: 0.9rem;">
                    <?php foreach ($budgetData['warnings'] as $warn): ?>
                        <li><?= $warn ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BUDGET METRICS OVERVIEW -->
    <div class="stats-grid">
        <div class="stat-card card-budget">
            <div class="stat-info">
                <span class="stat-label">Total Monthly Budget</span>
                <span class="stat-value" style="color: var(--primary);"><?= format_currency($budgetData['total_budget']) ?></span>
                <span class="stat-subtext"><i class="bi bi-calendar-check"></i> Planned for <?= date('M Y', strtotime($selectedMonth . '-01')) ?></span>
            </div>
            <div class="stat-icon"><i class="bi bi-piggy-bank"></i></div>
        </div>

        <div class="stat-card card-expense">
            <div class="stat-info">
                <span class="stat-label">Total Spent So Far</span>
                <span class="stat-value" style="color: var(--danger);"><?= format_currency($budgetData['total_spent']) ?></span>
                <span class="stat-subtext">
                    <?php 
                        $overallPct = $budgetData['total_budget'] > 0 ? round(($budgetData['total_spent'] / $budgetData['total_budget']) * 100, 1) : 0;
                    ?>
                    <?= $overallPct ?>% of total budget consumed
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-cart-x"></i></div>
        </div>

        <div class="stat-card card-income">
            <div class="stat-info">
                <span class="stat-label">Remaining Spendable</span>
                <span class="stat-value" style="color: <?= $totalRemaining > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                    <?= format_currency($totalRemaining) ?>
                </span>
                <span class="stat-subtext"><i class="bi bi-safe"></i> Before hitting overall budget limit</span>
            </div>
            <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
        </div>

        <div class="stat-card card-balance">
            <div class="stat-info">
                <span class="stat-label">Safe Daily Pacing</span>
                <span class="stat-value" style="color: var(--primary);"><?= format_currency($dailySpendLimit) ?></span>
                <span class="stat-subtext"><i class="bi bi-hourglass-split"></i> Over <?= $daysLeft ?> remaining days</span>
            </div>
            <div class="stat-icon"><i class="bi bi-speedometer2"></i></div>
        </div>
    </div>

    <!-- UNBUDGETED SPENDING NOTICE -->
    <?php if (!empty($budgetData['unbudgeted'])): ?>
    <div class="alert alert-info" style="margin-bottom: 1.5rem;">
        <div class="alert-content">
            <i class="bi bi-info-circle-fill alert-icon"></i>
            <div>
                <strong>Unbudgeted Spending This Month:</strong>
                <span style="font-size: 0.88rem; display: block; margin-top: 0.2rem;">
                    You have spent money in category(ies) without a planned limit:
                    <?php foreach ($budgetData['unbudgeted'] as $ub): ?>
                        <span class="badge badge-category" style="margin: 0.2rem; cursor: pointer;" 
                              onclick="document.getElementById('budgetCat').value='<?= htmlspecialchars(addslashes($ub['category'])) ?>'; document.getElementById('budgetLimit').focus();">
                            <?= htmlspecialchars($ub['category']) ?>: <?= format_currency($ub['spent']) ?>
                        </span>
                    <?php endforeach; ?>
                    &mdash; <em>Click any category above to quickly set its budget!</em>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2 COLUMN LAYOUT: SET BUDGET FORM & ACTIVE PROGRESS LIST -->
    <div class="two-col-reverse-layout">
        
        <!-- Set/Update Budget Form Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-sliders"></i> Set Category Budget</h2>
                <span class="badge badge-category"><?= $selectedMonth ?></span>
            </div>

            <form action="budget.php" method="POST">
                <input type="hidden" name="action" value="save_budget">
                <input type="hidden" name="month_year" value="<?= htmlspecialchars($selectedMonth) ?>">

                <div class="form-group">
                    <label class="form-label" for="budgetCat">Category <span class="required">*</span></label>
                    <select name="category" id="budgetCat" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($expenseCategories as $ec): ?>
                            <option value="<?= htmlspecialchars($ec['name']) ?>"><?= htmlspecialchars($ec['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="budgetLimit">
                        Monthly Spending Limit <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="1" name="monthly_limit" id="budgetLimit" 
                               class="form-control" placeholder="100.00" required>
                    </div>
                    <div class="preset-pills">
                        <button type="button" class="preset-pill" onclick="document.getElementById('budgetLimit').value='500.00'">₹500</button>
                        <button type="button" class="preset-pill" onclick="document.getElementById('budgetLimit').value='1000.00'">₹1,000</button>
                        <button type="button" class="preset-pill" onclick="document.getElementById('budgetLimit').value='2000.00'">₹2,000</button>
                        <button type="button" class="preset-pill" onclick="document.getElementById('budgetLimit').value='5000.00'">₹5,000</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Month</label>
                    <input type="text" class="form-control" value="<?= date('F Y', strtotime($selectedMonth . '-01')) ?>" readonly style="background-color: var(--secondary-light);">
                    <span class="form-hint">To plan for a different month, use the month picker at top right.</span>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                    <i class="bi bi-save"></i> Save Budget Target
                </button>
            </form>

            <div style="margin-top: 1.5rem; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                <strong>💡 Rule of Thumb:</strong> Give yourself a 10% buffer on Food & Dining to cover unexpected social outings or exam late-night study sessions!
            </div>
        </div>

        <!-- Category Budget Progress Cards -->
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <div>
                        <h2 class="card-title"><i class="bi bi-pie-chart-fill"></i> Category Budget Progress</h2>
                        <p class="card-subtitle">Real-time spend tracking for <?= date('F Y', strtotime($selectedMonth . '-01')) ?></p>
                    </div>
                    <span class="badge badge-category"><?= count($budgetData['items']) ?> Categories Tracked</span>
                </div>

                <?php if (empty($budgetData['items'])): ?>
                    <div style="text-align: center; padding: 2.5rem 1rem;">
                        <i class="bi bi-sliders" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 0.75rem; display: block;"></i>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">No category budgets set for this month yet.</p>
                        <p style="color: var(--text-light); font-size: 0.85rem;">Use the form on the left to set your first spending limit!</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($budgetData['items'] as $item): 
                            $barClass = 'progress-success';
                            $borderClass = 'border-success';
                            if ($item['percentage'] >= 100) {
                                $barClass = 'progress-danger';
                                $borderClass = 'border-danger';
                            } elseif ($item['percentage'] >= 80) {
                                $barClass = 'progress-warning';
                                $borderClass = 'border-warning';
                            }
                            $displayPct = min(100, $item['percentage']);
                        ?>
                        <div class="budget-item-card <?= $borderClass ?>">
                            <div class="budget-header">
                                <div class="budget-cat-name">
                                    <?= htmlspecialchars($item['category']) ?>
                                    <?php if ($item['status'] === 'danger'): ?>
                                        <span class="badge badge-expense" style="margin-left: 0.4rem;"><i class="bi bi-exclamation-triangle"></i> Exceeded</span>
                                    <?php elseif ($item['status'] === 'warning'): ?>
                                        <span class="badge badge-warning" style="margin-left: 0.4rem;"><i class="bi bi-exclamation-circle"></i> Near Limit</span>
                                    <?php else: ?>
                                        <span class="badge badge-success" style="margin-left: 0.4rem;"><i class="bi bi-check2"></i> On Track</span>
                                    <?php endif; ?>
                                </div>
                                <div class="budget-amounts">
                                    <strong><?= format_currency($item['spent']) ?></strong> of <?= format_currency($item['limit']) ?>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress-bar-container" style="height: 12px;">
                                <div class="progress-bar <?= $barClass ?>" style="width: <?= $displayPct ?>%;"></div>
                            </div>

                            <div class="budget-footer">
                                <span>
                                    <?php if ($item['remaining'] >= 0): ?>
                                        <strong><?= format_currency($item['remaining']) ?></strong> remaining
                                    <?php else: ?>
                                        <strong style="color: var(--danger);"><?= format_currency(abs($item['remaining'])) ?> over budget</strong>
                                    <?php endif; ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span><?= $item['percentage'] ?>% used</span>
                                    <a href="budget.php?month=<?= urlencode($selectedMonth) ?>&delete=<?= $item['id'] ?>" 
                                       class="action-btn delete-btn" 
                                       title="Remove Budget Target"
                                       onclick="return confirmDelete('Remove budget limit for <?= htmlspecialchars(addslashes($item['category'])) ?>?');"
                                       style="width: 24px; height: 24px; font-size: 0.75rem;">
                                        <i class="bi bi-x"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

