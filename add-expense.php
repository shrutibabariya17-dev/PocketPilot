<?php
// add-expense.php - Record a New Student Expense
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$amount = '';
$category = '';
$date = date('Y-m-d');
$paymentMethod = 'UPI / Online';
$description = '';

// Handle Form Submission BEFORE sending HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $category = sanitize($_POST['category'] ?? '');
    $date = sanitize($_POST['date'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'Cash');
    $description = sanitize($_POST['description'] ?? '');

    // Server-side validation
    if ($amount === false || $amount <= 0) {
        $errors[] = "Please enter a valid expense amount greater than 0.";
    }
    if (empty($category)) {
        $errors[] = "Please select an expense category.";
    }
    if (empty($date)) {
        $errors[] = "Please select a valid date.";
    }
    if (empty($description)) {
        $errors[] = "Please provide a short description or note for this expense.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO transactions (type, amount, category, date, payment_method, description)
                VALUES ('expense', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$amount, $category, $date, $paymentMethod, $description]);

            set_flash('success', "Expense of <strong>" . format_currency($amount) . "</strong> for '{$description}' logged successfully!");
            header("Location: add-expense.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch expense categories
$categories = get_categories($pdo, 'expense');

// Fetch 5 most recent expenses
$stmtRecent = $pdo->query("SELECT * FROM transactions WHERE type = 'expense' ORDER BY date DESC, id DESC LIMIT 5");
$recentExpenses = $stmtRecent->fetchAll();

// HTML Layout Starts Here
$pageTitle = "Add Expense";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Add New Expense</h1>
            <p class="page-subtitle">Track your student outflows — from campus meals to study supplies</p>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="btn btn-outline btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <a href="transactions.php" class="btn btn-outline btn-sm">
                <i class="bi bi-clock-history"></i> View All Transactions
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div class="alert-content">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div>
                    <strong>Please fix the following:</strong>
                    <ul style="margin: 0.35rem 0 0 1.25rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
        </div>
    <?php endif; ?>

    <div class="two-col-layout">
        
        <!-- Add Expense Form Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-dash-circle-fill" style="color: var(--danger);"></i> Expense Details</h2>
                <span class="badge badge-expense">Outflow</span>
            </div>

            <form action="add-expense.php" method="POST" id="expenseForm">
                
                <!-- Amount Input with Prefix & Presets -->
                <div class="form-group">
                    <label class="form-label" for="amountInput">
                        Expense Amount <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" 
                               class="form-control" placeholder="0.00" 
                               value="<?= htmlspecialchars((string)$amount) ?>" required autofocus>
                    </div>
                    <!-- Quick Presets -->
                    <div class="preset-pills">
                        <span style="font-size: 0.78rem; color: var(--text-muted); align-self: center;">Quick amounts:</span>
                        <button type="button" class="preset-pill" data-amount="50.00">₹50 Chai & Snack</button>
                        <button type="button" class="preset-pill" data-amount="150.00">₹150 Meal</button>
                        <button type="button" class="preset-pill" data-amount="400.00">₹400 Books</button>
                        <button type="button" class="preset-pill" data-amount="1000.00">₹1,000 Restock</button>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- Category Selection -->
                    <div class="form-group">
                        <label class="form-label" for="categorySelect">
                            Category <span class="required">*</span>
                        </label>
                        <select name="category" id="categorySelect" class="form-control" required>
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category === $cat['name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Input -->
                    <div class="form-group">
                        <label class="form-label" for="dateInput">
                            Date of Expense <span class="required">*</span>
                        </label>
                        <input type="date" name="date" id="dateInput" class="form-control" 
                               value="<?= htmlspecialchars($date) ?>" required max="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="form-group">
                    <label class="form-label" for="paymentMethodSelect">Payment Method</label>
                    <select name="payment_method" id="paymentMethodSelect" class="form-control">
                        <option value="UPI / Online" <?= $paymentMethod === 'UPI / Online' ? 'selected' : '' ?>>UPI / Google Pay / PhonePe / Online</option>
                        <option value="Cash" <?= $paymentMethod === 'Cash' ? 'selected' : '' ?>>Cash / Pocket Cash</option>
                        <option value="Debit Card" <?= $paymentMethod === 'Debit Card' ? 'selected' : '' ?>>Debit Card / Student Card</option>
                        <option value="Credit Card" <?= $paymentMethod === 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                        <option value="Bank Transfer" <?= $paymentMethod === 'Bank Transfer' ? 'selected' : '' ?>>Net Banking / Bank Transfer</option>
                    </select>
                </div>

                <!-- Description / Purpose -->
                <div class="form-group">
                    <label class="form-label" for="descriptionInput">
                        Description / Note <span class="required">*</span>
                    </label>
                    <input type="text" name="description" id="descriptionInput" class="form-control" 
                           placeholder="e.g. Canteen lunch, Calculus textbook, Hostel laundry token" 
                           value="<?= htmlspecialchars($description) ?>" required maxlength="255">
                    <span class="form-hint">Brief note explaining where this expense went</span>
                </div>

                <!-- Submit Button -->
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem; align-items: center;">
                    <button type="submit" class="btn btn-danger btn-lg" style="flex: 1;">
                        <i class="bi bi-check2-circle"></i> Save Expense Record
                    </button>
                    <button type="reset" class="btn btn-outline">Reset</button>
                </div>
            </form>
        </div>

        <!-- Right Side: Recent Expenses & Student Budget Advice -->
        <div>
            <!-- Recent 5 Expenses Card -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title" style="font-size: 1.05rem;">
                        <i class="bi bi-clock-history"></i> Recent Outflows
                    </h3>
                    <a href="transactions.php?type=expense" class="btn btn-outline btn-xs">All Expenses</a>
                </div>

                <?php if (empty($recentExpenses)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">
                        No expense records logged yet.
                    </p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($recentExpenses as $exp): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.5rem; border-bottom: 1px solid var(--border-light);">
                            <div>
                                <strong style="font-size: 0.92rem; color: var(--text-dark); display: block;"><?= htmlspecialchars($exp['description']) ?></strong>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($exp['category']) ?> &bull; <?= date('M d', strtotime($exp['date'])) ?>
                                </span>
                            </div>
                            <span class="amount-expense" style="font-size: 1rem;">
                                -<?= format_currency($exp['amount']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Rule Card -->
            <div class="card" style="background: var(--primary-light); border-color: #c7d2fe;">
                <h4 style="font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; font-size: 0.95rem;">
                    <i class="bi bi-info-circle-fill"></i> Fast Logging Tip
                </h4>
                <p style="font-size: 0.85rem; color: var(--text-body); line-height: 1.5;">
                    Always log small canteen snacks, printout fees, and bus fares. Small untracked purchases of ₹20 to ₹50 are the #1 reason students wonder where their monthly allowance disappeared!
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

