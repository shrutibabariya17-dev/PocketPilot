<?php
// income.php - Student Income Tracker
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$errors = [];
$amount = '';
$category = 'Pocket Money / Allowance';
$date = date('Y-m-d');
$paymentMethod = 'UPI / Online';
$description = '';

// Handle Income Addition BEFORE sending HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_income') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $category = sanitize($_POST['category'] ?? '');
    $date = sanitize($_POST['date'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'Bank Transfer');
    $description = sanitize($_POST['description'] ?? '');

    // Server-side validation
    if ($amount === false || $amount <= 0) {
        $errors[] = "Please enter a valid income amount greater than 0.";
    }
    if (empty($category)) {
        $errors[] = "Please select an income source or category.";
    }
    if (empty($date)) {
        $errors[] = "Please select the date received.";
    }
    if (empty($description)) {
        $errors[] = "Please provide a description (e.g. 'October Home Allowance', 'Library Assistant Pay').";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO transactions (type, amount, category, date, payment_method, description)
                VALUES ('income', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$amount, $category, $date, $paymentMethod, $description]);

            set_flash('success', "Income deposit of <strong>" . format_currency($amount) . "</strong> from '{$category}' added successfully!");
            header("Location: income.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Handle Income Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_income') {
    $editId = (int)($_POST['id'] ?? 0);
    $editAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $editCat = sanitize($_POST['category'] ?? '');
    $editDate = sanitize($_POST['date'] ?? '');
    $editMethod = sanitize($_POST['payment_method'] ?? 'Bank Transfer');
    $editDesc = sanitize($_POST['description'] ?? '');

    if ($editId <= 0 || $editAmount === false || $editAmount <= 0 || empty($editCat) || empty($editDate) || empty($editDesc)) {
        $errors[] = "Please provide valid information for all required fields.";
    } else {
        try {
            $stmtUpInc = $pdo->prepare("
                UPDATE transactions 
                SET amount = ?, category = ?, date = ?, payment_method = ?, description = ? 
                WHERE id = ? AND type = 'income'
            ");
            $stmtUpInc->execute([$editAmount, $editCat, $editDate, $editMethod, $editDesc, $editId]);
            set_flash('success', "Income record #{$editId} updated successfully!");
            header("Location: income.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}

// Handle Income Deletion
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmtDel = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND type = 'income'");
        $stmtDel->execute([$deleteId]);
        set_flash('success', "Income record deleted successfully.");
        header("Location: income.php");
        exit;
    } catch (PDOException $e) {
        set_flash('danger', "Error deleting record: " . $e->getMessage());
    }
}

// Fetch all income categories
$incomeCategories = get_categories($pdo, 'income');

// Income summary metrics
$stmtMonthInc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'income' AND DATE_FORMAT(date, '%Y-%m') = ?");
$stmtMonthInc->execute([CURRENT_MONTH]);
$monthIncome = (float)$stmtMonthInc->fetch()['total'];

$stmtLifeInc = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count FROM transactions WHERE type = 'income'");
$lifeRow = $stmtLifeInc->fetch();
$lifetimeIncome = (float)$lifeRow['total'];
$incomeCount = (int)$lifeRow['count'];

// Income source breakdown
$stmtBreakdown = $pdo->query("
    SELECT category, SUM(amount) as total, COUNT(*) as entries 
    FROM transactions 
    WHERE type = 'income' 
    GROUP BY category 
    ORDER BY total DESC
");
$incomeBreakdown = $stmtBreakdown->fetchAll();
$topSource = !empty($incomeBreakdown) ? $incomeBreakdown[0]['category'] : 'None yet';

// Fetch all income records
$stmtList = $pdo->query("SELECT * FROM transactions WHERE type = 'income' ORDER BY date DESC, id DESC");
$incomeList = $stmtList->fetchAll();

// HTML Layout Starts Here
$pageTitle = "Income Tracker";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Student Income Tracker</h1>
            <p class="page-subtitle">Track pocket money, scholarships, tutoring earnings, and financial support</p>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="btn btn-outline btn-sm"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="add-expense.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-dash-circle"></i> Add Expense</a>
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

    <!-- INCOME SUMMARY METRICS -->
    <div class="stats-grid">
        <div class="stat-card card-income">
            <div class="stat-info">
                <span class="stat-label">Income This Month</span>
                <span class="stat-value" style="color: var(--success);"><?= format_currency($monthIncome) ?></span>
                <span class="stat-subtext"><i class="bi bi-calendar3"></i> <?= date('F Y') ?></span>
            </div>
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        </div>

        <div class="stat-card card-balance">
            <div class="stat-info">
                <span class="stat-label">Total Inflows Tracked</span>
                <span class="stat-value" style="color: var(--primary);"><?= format_currency($lifetimeIncome) ?></span>
                <span class="stat-subtext"><i class="bi bi-check-all"></i> Across <?= $incomeCount ?> deposit(s)</span>
            </div>
            <div class="stat-icon"><i class="bi bi-bank"></i></div>
        </div>

        <div class="stat-card card-budget">
            <div class="stat-info">
                <span class="stat-label">Primary Income Source</span>
                <span class="stat-value" style="font-size: 1.35rem; color: var(--text-dark);"><?= htmlspecialchars($topSource) ?></span>
                <span class="stat-subtext"><i class="bi bi-trophy"></i> Largest contributor</span>
            </div>
            <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
        </div>
    </div>

    <!-- 2 COLUMN LAYOUT: ADD FORM & BREAKDOWN -->
    <div class="two-col-layout" style="margin-bottom: 2.5rem;">
        
        <!-- Add Income Form Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-plus-circle-fill" style="color: var(--success);"></i> Record New Income</h2>
                <span class="badge badge-income">Inflow</span>
            </div>

            <form action="income.php" method="POST">
                <input type="hidden" name="action" value="add_income">

                <div class="form-group">
                    <label class="form-label" for="amountInput">
                        Income Amount <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" 
                               class="form-control" placeholder="0.00" 
                               value="<?= htmlspecialchars((string)$amount) ?>" required>
                    </div>
                    <div class="preset-pills">
                        <span style="font-size: 0.78rem; color: var(--text-muted); align-self: center;">Quick presets:</span>
                        <button type="button" class="preset-pill" data-amount="2000.00">₹2,000 Allowance</button>
                        <button type="button" class="preset-pill" data-amount="5000.00">₹5,000 Wages</button>
                        <button type="button" class="preset-pill" data-amount="10000.00">₹10,000 Grant</button>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="incomeCategory">
                            Income Source / Category <span class="required">*</span>
                        </label>
                        <select name="category" id="incomeCategory" class="form-control" required>
                            <?php foreach ($incomeCategories as $icat): ?>
                                <option value="<?= htmlspecialchars($icat['name']) ?>" <?= $category === $icat['name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($icat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="incomeDate">
                            Date Received <span class="required">*</span>
                        </label>
                        <input type="date" name="date" id="incomeDate" class="form-control" 
                               value="<?= htmlspecialchars($date) ?>" required max="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="incomeMethod">Deposit Method</label>
                    <select name="payment_method" id="incomeMethod" class="form-control">
                        <option value="UPI / Online">UPI / Online / GPay / Venmo</option>
                        <option value="Bank Transfer" selected>Direct Bank Transfer / Wire</option>
                        <option value="Cash">Cash Handed / Pocket Cash</option>
                        <option value="Debit Card">Debit / Cheque</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="incomeDescription">
                        Description / Source Note <span class="required">*</span>
                    </label>
                    <input type="text" name="description" id="incomeDescription" class="form-control" 
                           placeholder="e.g. October Pocket Money from Mom & Dad, Fall Semester Scholarship, Math tutoring fee" 
                           value="<?= htmlspecialchars($description) ?>" required maxlength="255">
                </div>

                <button type="submit" class="btn btn-success btn-lg" style="width: 100%; margin-top: 0.5rem;">
                    <i class="bi bi-wallet2"></i> Add Income Deposit
                </button>
            </form>
        </div>

        <!-- Income Sources Breakdown -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-pie-chart"></i> Income by Source</h3>
                <span class="card-subtitle">Lifetime distribution</span>
            </div>

            <?php if (empty($incomeBreakdown)): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem 0;">
                    No income sources recorded yet.
                </p>
            <?php else: ?>
                <div class="breakdown-list">
                    <?php 
                        $totalAllInc = max(1, $lifetimeIncome);
                        foreach ($incomeBreakdown as $b): 
                            $bPct = round(($b['total'] / $totalAllInc) * 100, 1);
                    ?>
                    <div class="breakdown-row">
                        <div class="breakdown-info">
                            <span><?= htmlspecialchars($b['category']) ?></span>
                            <span><?= format_currency($b['total']) ?> <small style="color: var(--text-muted);">(<?= $bPct ?>%)</small></span>
                        </div>
                        <div class="progress-bar-container" style="height: 8px;">
                            <div class="progress-bar progress-success" style="width: <?= $bPct ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1.5rem; background: var(--success-light); padding: 1rem; border-radius: var(--radius-md); font-size: 0.85rem; color: #065f46;">
                <i class="bi bi-check-circle-fill"></i> <strong>Pro Student Tip:</strong> Diversifying your income with just 4-6 hours of campus tutoring or research assistantships can cover your entire monthly textbook and leisure budget!
            </div>
        </div>
    </div>

    <!-- COMPLETE INCOME RECORDS TABLE -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><i class="bi bi-journal-text"></i> All Recorded Income Inflows</h2>
                <p class="card-subtitle"><?= count($incomeList) ?> total income record(s) on file</p>
            </div>
            <a href="transactions.php" class="btn btn-outline btn-xs">Transaction History &rarr;</a>
        </div>

        <?php if (empty($incomeList)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem;">
                <p style="color: var(--text-muted);">No income entries found. Add your first income above!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Source / Category</th>
                            <th>Payment Method</th>
                            <th style="text-align: right;">Amount</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incomeList as $inc): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.85rem; color: var(--text-muted);">
                                <?= date('M d, Y', strtotime($inc['date'])) ?>
                            </td>
                            <td>
                                <strong style="color: var(--text-dark); display: block;"><?= htmlspecialchars($inc['description']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-income">
                                    <?= htmlspecialchars($inc['category']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($inc['payment_method']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="amount-income">+<?= format_currency($inc['amount']) ?></span>
                            </td>
                            <td style="text-align: center;">
                                <div class="table-actions" style="justify-content: center;">
                                    <button type="button" class="action-btn" title="Edit Income"
                                            onclick='openEditIncomeModal(<?= json_encode($inc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="income.php?delete=<?= $inc['id'] ?>" 
                                       class="action-btn delete-btn" 
                                       title="Delete Record"
                                       onclick="return confirmDelete('Are you sure you want to delete this income entry of <?= format_currency($inc['amount']) ?>?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- EDIT INCOME MODAL -->
<div class="modal-backdrop" id="editIncomeModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Income Record</h3>
            <button type="button" class="btn-close" onclick="closeModal('editIncomeModal')"><i class="bi bi-x"></i></button>
        </div>

        <form action="income.php" method="POST">
            <input type="hidden" name="action" value="edit_income">
            <input type="hidden" name="id" id="editIncomeId">

            <div class="form-group">
                <label class="form-label" for="editIncomeAmount">Amount <span class="required">*</span></label>
                <div class="input-with-icon">
                    <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                    <input type="number" step="0.01" min="0.01" name="amount" id="editIncomeAmount" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="editIncomeCategory">Income Source / Category <span class="required">*</span></label>
                <select name="category" id="editIncomeCategory" class="form-control" required>
                    <?php foreach ($incomeCategories as $icat): ?>
                        <option value="<?= htmlspecialchars($icat['name']) ?>"><?= htmlspecialchars($icat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="editIncomeDate">Date Received <span class="required">*</span></label>
                    <input type="date" name="date" id="editIncomeDate" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="editIncomeMethod">Deposit Method</label>
                    <select name="payment_method" id="editIncomeMethod" class="form-control">
                        <option value="UPI / Online">UPI / Online</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash Handed</option>
                        <option value="Debit Card">Debit / Cheque</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="editIncomeDescription">Description <span class="required">*</span></label>
                <input type="text" name="description" id="editIncomeDescription" class="form-control" required maxlength="255">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editIncomeModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Update Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditIncomeModal(inc) {
    document.getElementById('editIncomeId').value = inc.id;
    document.getElementById('editIncomeAmount').value = parseFloat(inc.amount).toFixed(2);
    document.getElementById('editIncomeCategory').value = inc.category;
    document.getElementById('editIncomeDate').value = inc.date;
    document.getElementById('editIncomeMethod').value = inc.payment_method;
    document.getElementById('editIncomeDescription').value = inc.description;
    openModal('editIncomeModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

