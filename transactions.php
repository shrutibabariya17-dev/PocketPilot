<?php
// transactions.php - Comprehensive Student Transaction History
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

// Handle Transaction Deletion
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        $stmtDel = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $stmtDel->execute([$delId]);
        set_flash('success', "Transaction #{$delId} was deleted successfully.");
        header("Location: transactions.php");
        exit;
    } catch (PDOException $e) {
        set_flash('danger', "Error deleting transaction: " . $e->getMessage());
    }
}

// Handle Transaction Editing
$editErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_transaction') {
    $editId = (int)($_POST['id'] ?? 0);
    $editType = sanitize($_POST['type'] ?? 'expense');
    $editAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $editCategory = sanitize($_POST['category'] ?? '');
    $editDate = sanitize($_POST['date'] ?? '');
    $editPayment = sanitize($_POST['payment_method'] ?? 'Cash');
    $editDesc = sanitize($_POST['description'] ?? '');

    if ($editId <= 0) {
        $editErrors[] = "Invalid transaction ID.";
    }
    if ($editAmount === false || $editAmount <= 0) {
        $editErrors[] = "Amount must be a positive number.";
    }
    if (empty($editCategory)) {
        $editErrors[] = "Category is required.";
    }
    if (empty($editDate)) {
        $editErrors[] = "Date is required.";
    }
    if (empty($editDesc)) {
        $editErrors[] = "Description is required.";
    }

    if (empty($editErrors)) {
        try {
            $stmtUp = $pdo->prepare("
                UPDATE transactions 
                SET type = ?, amount = ?, category = ?, date = ?, payment_method = ?, description = ?
                WHERE id = ?
            ");
            $stmtUp->execute([$editType, $editAmount, $editCategory, $editDate, $editPayment, $editDesc, $editId]);
            set_flash('success', "Transaction #{$editId} updated successfully!");
            header("Location: transactions.php");
            exit;
        } catch (PDOException $e) {
            $editErrors[] = "Database update error: " . $e->getMessage();
        }
    }
}

// Read Filter Parameters
$search = sanitize($_GET['search'] ?? '');
$typeFilter = sanitize($_GET['type'] ?? 'all');
$categoryFilter = sanitize($_GET['category'] ?? 'all');
$startDate = sanitize($_GET['start_date'] ?? '');
$endDate = sanitize($_GET['end_date'] ?? '');

// Build Query
$sql = "SELECT * FROM transactions WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND description LIKE ?";
    $params[] = "%" . $search . "%";
}

if ($typeFilter === 'income' || $typeFilter === 'expense') {
    $sql .= " AND type = ?";
    $params[] = $typeFilter;
}

if (!empty($categoryFilter) && $categoryFilter !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if (!empty($startDate)) {
    $sql .= " AND date >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $sql .= " AND date <= ?";
    $params[] = $endDate;
}

$sql .= " ORDER BY date DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pocketpilot_transactions_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['ID', 'Type', 'Amount (INR)', 'Category', 'Date', 'Payment Method', 'Description']);
    foreach ($transactions as $row) {
        fputcsv($out, [
            $row['id'],
            ucfirst($row['type']),
            $row['amount'],
            $row['category'],
            $row['date'],
            $row['payment_method'],
            $row['description']
        ]);
    }
    fclose($out);
    exit;
}

// Now include header for HTML page
$pageTitle = "Transaction History";
require_once __DIR__ . '/includes/header.php';

// Calculate filtered aggregates
$filteredIncome = 0;
$filteredExpense = 0;
foreach ($transactions as $t) {
    if ($t['type'] === 'income') {
        $filteredIncome += (float)$t['amount'];
    } else {
        $filteredExpense += (float)$t['amount'];
    }
}
$filteredNet = $filteredIncome - $filteredExpense;

// Fetch all distinct categories for filter dropdown
$allCategories = get_categories($pdo);
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Transaction History</h1>
            <p class="page-subtitle">Search, filter, edit, and audit your student financial transactions</p>
        </div>
        <div class="header-actions">
            <a href="transactions.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline btn-sm" title="Export to CSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </a>
            <a href="add-expense.php" class="btn btn-danger btn-sm">
                <i class="bi bi-dash-circle"></i> Add Expense
            </a>
            <a href="income.php" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Add Income
            </a>
        </div>
    </div>

    <?php if (!empty($editErrors)): ?>
        <div class="alert alert-danger">
            <div class="alert-content">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div>
                    <strong>Update Failed:</strong>
                    <ul style="margin: 0.35rem 0 0 1.25rem;">
                        <?php foreach ($editErrors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- FILTER & SEARCH CARD -->
    <div class="filter-card">
        <form action="transactions.php" method="GET" class="filter-grid">
            
            <!-- Search Keyword -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.8rem;">Search Description</label>
                <div class="input-with-icon">
                    <span class="input-prefix"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search keywords..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <!-- Type Filter -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.8rem;">Type</label>
                <select name="type" class="form-control">
                    <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All Types</option>
                    <option value="expense" <?= $typeFilter === 'expense' ? 'selected' : '' ?>>Expenses Only</option>
                    <option value="income" <?= $typeFilter === 'income' ? 'selected' : '' ?>>Income Only</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.8rem;">Category</label>
                <select name="category" class="form-control">
                    <option value="all">All Categories</option>
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?= htmlspecialchars($c['name']) ?>" <?= $categoryFilter === $c['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?> (<?= ucfirst($c['type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range Filters -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.8rem;">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>

            <!-- Filter Buttons -->
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" title="Apply Filter">
                    <i class="bi bi-funnel-fill"></i> Filter
                </button>
                <a href="transactions.php" class="btn btn-outline" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- FILTER SUMMARY BAR -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.88rem; color: var(--text-dark);">
            Showing <strong><?= count($transactions) ?></strong> transaction(s)
            <?php if (!empty($search) || $typeFilter !== 'all' || $categoryFilter !== 'all' || !empty($startDate)): ?>
                <span class="badge badge-category" style="margin-left: 0.5rem;">Filtered view</span>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 1.5rem; font-size: 0.88rem; font-weight: 600;">
            <span style="color: var(--success);"><i class="bi bi-arrow-down-left"></i> Income: +<?= format_currency($filteredIncome) ?></span>
            <span style="color: var(--danger);"><i class="bi bi-arrow-up-right"></i> Expenses: -<?= format_currency($filteredExpense) ?></span>
            <span style="color: <?= $filteredNet >= 0 ? 'var(--primary)' : 'var(--danger)' ?>;">Net: <?= format_currency($filteredNet) ?></span>
        </div>
    </div>

    <!-- TRANSACTIONS TABLE CARD -->
    <div class="card">
        <?php if (empty($transactions)): ?>
            <div style="text-align: center; padding: 3rem 1rem;">
                <i class="bi bi-search" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 0.75rem; display: block;"></i>
                <h3 style="font-size: 1.15rem; color: var(--text-dark); margin-bottom: 0.25rem;">No matching transactions found</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">Try adjusting your search criteria or clear active filters.</p>
                <a href="transactions.php" class="btn btn-outline btn-sm">Clear All Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Payment Method</th>
                            <th style="text-align: right;">Amount</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                        <tr id="row-<?= $t['id'] ?>">
                            <td>
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="badge badge-income"><i class="bi bi-arrow-down"></i> Income</span>
                                <?php else: ?>
                                    <span class="badge badge-expense"><i class="bi bi-arrow-up"></i> Expense</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap; font-size: 0.85rem; color: var(--text-muted);">
                                <?= date('M d, Y', strtotime($t['date'])) ?>
                            </td>
                            <td>
                                <strong style="color: var(--text-dark); display: block; font-size: 0.92rem;">
                                    <?= htmlspecialchars($t['description']) ?>
                                </strong>
                            </td>
                            <td>
                                <span class="badge badge-category">
                                    <?= htmlspecialchars($t['category']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($t['payment_method']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="amount-income">+<?= format_currency($t['amount']) ?></span>
                                <?php else: ?>
                                    <span class="amount-expense">-<?= format_currency($t['amount']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="table-actions" style="justify-content: center;">
                                    <!-- Edit Trigger Button -->
                                    <button type="button" class="action-btn" title="Edit Transaction" 
                                            onclick='openEditModal(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <!-- Delete Trigger Button -->
                                    <a href="transactions.php?delete=<?= $t['id'] ?>" 
                                       class="action-btn delete-btn" 
                                       title="Delete Transaction"
                                       onclick="return confirmDelete('Are you sure you want to delete transaction #<?= $t['id'] ?> (<?= htmlspecialchars(addslashes($t['description'])) ?>)?');">
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

<!-- EDIT TRANSACTION MODAL -->
<div class="modal-backdrop" id="editModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Transaction</h3>
            <button type="button" class="btn-close" onclick="closeModal('editModal')"><i class="bi bi-x"></i></button>
        </div>

        <form action="transactions.php" method="POST" id="editForm">
            <input type="hidden" name="action" value="edit_transaction">
            <input type="hidden" name="id" id="editId">

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Type</label>
                    <select name="type" id="editType" class="form-control" required>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="editAmount" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" id="editCategory" class="form-control" required>
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" id="editDate" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="editPayment" class="form-control">
                        <option value="UPI / Online">UPI / Online</option>
                        <option value="Cash">Cash</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description / Note</label>
                <input type="text" name="description" id="editDesc" class="form-control" required maxlength="255">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(t) {
    document.getElementById('editId').value = t.id;
    document.getElementById('editType').value = t.type;
    document.getElementById('editAmount').value = parseFloat(t.amount).toFixed(2);
    document.getElementById('editCategory').value = t.category;
    document.getElementById('editDate').value = t.date;
    document.getElementById('editPayment').value = t.payment_method;
    document.getElementById('editDesc').value = t.description;
    openModal('editModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

