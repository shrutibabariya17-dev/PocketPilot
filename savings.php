<?php
// savings.php - Student Savings Goals & Milestone Tracker
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$errors = [];

// 1. Handle Create New Goal BEFORE sending HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_goal') {
    $title = sanitize($_POST['title'] ?? '');
    $targetAmount = filter_input(INPUT_POST, 'target_amount', FILTER_VALIDATE_FLOAT);
    $initialSaved = filter_input(INPUT_POST, 'initial_saved', FILTER_VALIDATE_FLOAT) ?: 0.0;
    $targetDate = sanitize($_POST['target_date'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($title)) {
        $errors[] = "Goal title is required.";
    }
    if ($targetAmount === false || $targetAmount <= 0) {
        $errors[] = "Target amount must be greater than 0.";
    }
    if (empty($targetDate)) {
        $errors[] = "Target completion date is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO savings_goals (title, target_amount, current_amount, target_date, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $targetAmount, $initialSaved, $targetDate, $notes]);
            $goalId = $pdo->lastInsertId();

            if ($initialSaved > 0) {
                $stmtC = $pdo->prepare("
                    INSERT INTO savings_contributions (goal_id, amount, contribution_date, note)
                    VALUES (?, ?, CURRENT_DATE, 'Initial seed amount')
                ");
                $stmtC->execute([$goalId, $initialSaved]);
            }

            set_flash('success', "Savings goal '<strong>{$title}</strong>' created with target " . format_currency($targetAmount) . "!");
            header("Location: savings.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

// 2. Handle Record Contribution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_contribution') {
    $goalId = (int)($_POST['goal_id'] ?? 0);
    $cAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $cDate = sanitize($_POST['date'] ?? date('Y-m-d'));
    $cNote = sanitize($_POST['note'] ?? 'Contribution towards goal');

    if ($goalId <= 0) {
        $errors[] = "Invalid savings goal selected.";
    }
    if ($cAmount === false || $cAmount <= 0) {
        $errors[] = "Contribution amount must be greater than 0.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert contribution log
            $stmtIns = $pdo->prepare("
                INSERT INTO savings_contributions (goal_id, amount, contribution_date, note)
                VALUES (?, ?, ?, ?)
            ");
            $stmtIns->execute([$goalId, $cAmount, $cDate, $cNote]);

            // Update goal current amount
            $stmtUpd = $pdo->prepare("
                UPDATE savings_goals 
                SET current_amount = current_amount + ? 
                WHERE id = ?
            ");
            $stmtUpd->execute([$cAmount, $goalId]);

            $pdo->commit();

            set_flash('success', "Contributed <strong>" . format_currency($cAmount) . "</strong> to your goal!");
            header("Location: savings.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Contribution error: " . $e->getMessage();
        }
    }
}

// 3. Handle Edit Goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_goal') {
    $editGoalId = (int)($_POST['id'] ?? 0);
    $editTitle = sanitize($_POST['title'] ?? '');
    $editTarget = filter_input(INPUT_POST, 'target_amount', FILTER_VALIDATE_FLOAT);
    $editTargetDate = sanitize($_POST['target_date'] ?? '');
    $editNotes = sanitize($_POST['notes'] ?? '');

    if ($editGoalId <= 0 || empty($editTitle) || $editTarget === false || $editTarget <= 0 || empty($editTargetDate)) {
        $errors[] = "Please provide valid information for all required fields.";
    } else {
        try {
            $stmtUpG = $pdo->prepare("
                UPDATE savings_goals 
                SET title = ?, target_amount = ?, target_date = ?, notes = ? 
                WHERE id = ?
            ");
            $stmtUpG->execute([$editTitle, $editTarget, $editTargetDate, $editNotes, $editGoalId]);
            set_flash('success', "Savings goal '{$editTitle}' updated successfully!");
            header("Location: savings.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Failed to update goal: " . $e->getMessage();
        }
    }
}

// 4. Handle Delete Goal
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        $stmtDel = $pdo->prepare("DELETE FROM savings_goals WHERE id = ?");
        $stmtDel->execute([$delId]);
        set_flash('success', "Savings goal deleted successfully.");
        header("Location: savings.php");
        exit;
    } catch (PDOException $e) {
        set_flash('danger', "Error deleting goal: " . $e->getMessage());
    }
}

// Fetch all savings goals
$stmtGoals = $pdo->query("SELECT * FROM savings_goals ORDER BY (current_amount >= target_amount) ASC, target_date ASC");
$goals = $stmtGoals->fetchAll();

// Fetch contributions grouped by goal
$stmtAllContrib = $pdo->query("SELECT * FROM savings_contributions ORDER BY contribution_date DESC, id DESC");
$contributionsByGoal = [];
foreach ($stmtAllContrib->fetchAll() as $c) {
    $contributionsByGoal[$c['goal_id']][] = $c;
}

// Overall metrics
$totalSavedAll = 0;
$totalTargetAll = 0;
$completedGoalsCount = 0;

foreach ($goals as $g) {
    $totalSavedAll += (float)$g['current_amount'];
    $totalTargetAll += (float)$g['target_amount'];
    if ($g['current_amount'] >= $g['target_amount']) {
        $completedGoalsCount++;
    }
}

// HTML Layout Starts Here
$pageTitle = "Savings Goals";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Savings Goals</h1>
            <p class="page-subtitle">Track targets for laptops, certifications, emergency cushions, and student trips</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" onclick="openModal('newGoalModal')">
                <i class="bi bi-plus-lg"></i> Create New Goal
            </button>
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

    <!-- SAVINGS STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card card-income">
            <div class="stat-info">
                <span class="stat-label">Total Amount Saved</span>
                <span class="stat-value" style="color: var(--success);"><?= format_currency($totalSavedAll) ?></span>
                <span class="stat-subtext"><i class="bi bi-piggy-bank-fill"></i> Across <?= count($goals) ?> goals</span>
            </div>
            <div class="stat-icon"><i class="bi bi-piggy-bank"></i></div>
        </div>

        <div class="stat-card card-balance">
            <div class="stat-info">
                <span class="stat-label">Combined Target Goals</span>
                <span class="stat-value" style="color: var(--primary);"><?= format_currency($totalTargetAll) ?></span>
                <span class="stat-subtext">
                    <?= $totalTargetAll > 0 ? round(($totalSavedAll / $totalTargetAll) * 100, 1) : 0 ?>% of combined goal funded
                </span>
            </div>
            <div class="stat-icon"><i class="bi bi-bullseye"></i></div>
        </div>

        <div class="stat-card card-budget">
            <div class="stat-info">
                <span class="stat-label">Goals Achieved</span>
                <span class="stat-value" style="color: var(--text-dark);"><?= $completedGoalsCount ?> / <?= count($goals) ?></span>
                <span class="stat-subtext"><i class="bi bi-trophy-fill text-warning"></i> Completed milestones</span>
            </div>
            <div class="stat-icon"><i class="bi bi-award-fill"></i></div>
        </div>
    </div>

    <!-- SAVINGS GOALS GRID -->
    <?php if (empty($goals)): ?>
        <div class="card" style="text-align: center; padding: 4rem 1.5rem;">
            <i class="bi bi-piggy-bank" style="font-size: 3.5rem; color: var(--text-light); margin-bottom: 1rem; display: block;"></i>
            <h2 style="font-size: 1.4rem; color: var(--text-dark); margin-bottom: 0.5rem;">No Savings Goals Created Yet</h2>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
                Whether you need ₹500 for a textbook, ₹2,000 for a weekend road trip, or ₹40,000 for a computer, defining a goal makes saving 3x more effective!
            </p>
            <button type="button" class="btn btn-primary btn-lg" onclick="openModal('newGoalModal')">
                <i class="bi bi-plus-lg"></i> Set Your First Goal
            </button>
        </div>
    <?php else: ?>
        <div class="goals-grid">
            <?php foreach ($goals as $goal): 
                $target = (float)$goal['target_amount'];
                $saved = (float)$goal['current_amount'];
                $pct = $target > 0 ? round(($saved / $target) * 100, 1) : 0;
                $displayPct = min(100, $pct);
                $isCompleted = ($saved >= $target);
                $remaining = max(0, $target - $saved);

                // Days left
                $targetDateObj = new DateTime($goal['target_date']);
                $todayObj = new DateTime('today');
                $diff = $todayObj->diff($targetDateObj);
                $daysRemaining = (int)$diff->format("%r%a");
            ?>
            <div class="goal-card" style="<?= $isCompleted ? 'border-color: #a7f3d0; background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);' : '' ?>">
                <div>
                    <div class="goal-header">
                        <div>
                            <h3 class="goal-title"><?= htmlspecialchars($goal['title']) ?></h3>
                            <div class="goal-target-date">
                                <i class="bi bi-calendar-event"></i> Target: <?= date('M d, Y', strtotime($goal['target_date'])) ?>
                                <?php if ($isCompleted): ?>
                                    <span class="badge badge-success" style="margin-left: 0.4rem;">🎉 Goal Achieved!</span>
                                <?php elseif ($daysRemaining > 0): ?>
                                    <span class="badge badge-category" style="margin-left: 0.4rem;"><?= $daysRemaining ?> days left</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="margin-left: 0.4rem;">Past due</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="savings.php?delete=<?= $goal['id'] ?>" class="action-btn delete-btn" title="Delete Goal"
                           onclick="return confirmDelete('Are you sure you want to remove the goal <?= htmlspecialchars(addslashes($goal['title'])) ?>?');">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>

                    <?php if (!empty($goal['notes'])): ?>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.45;">
                            <?= htmlspecialchars($goal['notes']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="goal-amount-row">
                        <div>
                            <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; display: block;">Saved</span>
                            <span class="goal-saved"><?= format_currency($saved) ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span class="goal-target">Target: <strong><?= format_currency($target) ?></strong></span>
                            <span style="font-size: 0.82rem; color: var(--text-muted); display: block;">
                                <?= $isCompleted ? 'Goal Reached!' : format_currency($remaining) . ' remaining' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-bar-container" style="height: 12px;">
                        <div class="progress-bar <?= $isCompleted ? 'progress-success' : 'progress-primary' ?>" style="width: <?= $displayPct ?>%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">
                        <span>Progress</span>
                        <strong><?= $pct ?>% funded</strong>
                    </div>
                </div>

                <div class="goal-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline-primary btn-sm" style="flex: 2; min-width: 120px;"
                            onclick="openContributeModal(<?= $goal['id'] ?>, '<?= htmlspecialchars(addslashes($goal['title'])) ?>')">
                        <i class="bi bi-plus-circle"></i> Contribute
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" style="flex: 1;"
                            onclick='openHistoryModal(<?= $goal['id'] ?>, "<?= htmlspecialchars(addslashes($goal['title'])) ?>", <?= json_encode($contributionsByGoal[$goal['id']] ?? []) ?>)'>
                        <i class="bi bi-clock-history"></i> History
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" style="padding: 0.45rem 0.65rem;" title="Edit Goal"
                            onclick='openEditGoalModal(<?= json_encode($goal, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- CREATE GOAL MODAL -->
<div class="modal-backdrop" id="newGoalModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-bullseye"></i> Create New Savings Goal</h3>
            <button type="button" class="btn-close" onclick="closeModal('newGoalModal')"><i class="bi bi-x"></i></button>
        </div>

        <form action="savings.php" method="POST">
            <input type="hidden" name="action" value="create_goal">

            <div class="form-group">
                <label class="form-label" for="goalTitle">Goal Title <span class="required">*</span></label>
                <input type="text" name="title" id="goalTitle" class="form-control" 
                       placeholder="e.g. New Coding Laptop, Emergency Cushion, Concert Ticket" required maxlength="100">
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="targetAmount">Target Amount <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="1" name="target_amount" id="targetAmount" class="form-control" placeholder="500.00" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="initialSaved">Initial Seed Amount</label>
                    <div class="input-with-icon">
                        <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                        <input type="number" step="0.01" min="0" name="initial_saved" id="initialSaved" class="form-control" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="targetDate">Target Completion Date <span class="required">*</span></label>
                <input type="date" name="target_date" id="targetDate" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="goalNotes">Notes / Motivation</label>
                <textarea name="notes" id="goalNotes" class="form-control" rows="2" placeholder="Why are you saving for this? Any milestones?"></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('newGoalModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD CONTRIBUTION MODAL -->
<div class="modal-backdrop" id="contributeModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-cash-stack"></i> Record Contribution</h3>
            <button type="button" class="btn-close" onclick="closeModal('contributeModal')"><i class="bi bi-x"></i></button>
        </div>

        <form action="savings.php" method="POST">
            <input type="hidden" name="action" value="add_contribution">
            <input type="hidden" name="goal_id" id="contribGoalId">

            <p style="font-size: 0.9rem; color: var(--text-dark); margin-bottom: 1.25rem;">
                Adding money to: <strong id="contribGoalTitle" style="color: var(--primary);"></strong>
            </p>

            <div class="form-group">
                <label class="form-label" for="contribAmount">Contribution Amount <span class="required">*</span></label>
                <div class="input-with-icon">
                    <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                    <input type="number" step="0.01" min="0.01" name="amount" id="contribAmount" class="form-control" placeholder="25.00" required autofocus>
                </div>
                <div class="preset-pills">
                    <button type="button" class="preset-pill" onclick="document.getElementById('contribAmount').value='100.00'">+₹100</button>
                    <button type="button" class="preset-pill" onclick="document.getElementById('contribAmount').value='250.00'">+₹250</button>
                    <button type="button" class="preset-pill" onclick="document.getElementById('contribAmount').value='500.00'">+₹500</button>
                    <button type="button" class="preset-pill" onclick="document.getElementById('contribAmount').value='1000.00'">+₹1,000</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="contribDate">Contribution Date</label>
                <input type="date" name="date" id="contribDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="contribNote">Note</label>
                <input type="text" name="note" id="contribNote" class="form-control" placeholder="e.g. Saved from weekend freelance work">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('contributeModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Deposit Savings</button>
            </div>
        </form>
    </div>
</div>

<script>
function openContributeModal(goalId, goalTitle) {
    document.getElementById('contribGoalId').value = goalId;
    document.getElementById('contribGoalTitle').textContent = goalTitle;
    document.getElementById('contribAmount').value = '';
    openModal('contributeModal');
}

function openEditGoalModal(goal) {
    document.getElementById('editGoalId').value = goal.id;
    document.getElementById('editGoalTitle').value = goal.title;
    document.getElementById('editGoalTarget').value = parseFloat(goal.target_amount).toFixed(2);
    document.getElementById('editGoalTargetDate').value = goal.target_date;
    document.getElementById('editGoalNotes').value = goal.notes || '';
    openModal('editGoalModal');
}

function openHistoryModal(goalId, goalTitle, history) {
    document.getElementById('historyGoalTitle').textContent = goalTitle;
    const body = document.getElementById('historyModalBody');
    if (!history || history.length === 0) {
        body.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">No contributions recorded for this goal yet.</p>';
    } else {
        let html = '<div style="display: flex; flex-direction: column; gap: 0.65rem;">';
        history.forEach(item => {
            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                    <div>
                        <strong style="font-size: 0.95rem; color: var(--success);">+₹${parseFloat(item.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">${item.note || 'Contribution'}</div>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">${item.contribution_date}</span>
                </div>
            `;
        });
        html += '</div>';
        body.innerHTML = html;
    }
    openModal('historyModal');
}
</script>

<!-- EDIT GOAL MODAL -->
<div class="modal-backdrop" id="editGoalModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Savings Goal</h3>
            <button type="button" class="btn-close" onclick="closeModal('editGoalModal')"><i class="bi bi-x"></i></button>
        </div>

        <form action="savings.php" method="POST">
            <input type="hidden" name="action" value="edit_goal">
            <input type="hidden" name="id" id="editGoalId">

            <div class="form-group">
                <label class="form-label" for="editGoalTitle">Goal Title <span class="required">*</span></label>
                <input type="text" name="title" id="editGoalTitle" class="form-control" required maxlength="100">
            </div>

            <div class="form-group">
                <label class="form-label" for="editGoalTarget">Target Amount <span class="required">*</span></label>
                <div class="input-with-icon">
                    <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                    <input type="number" step="0.01" min="1" name="target_amount" id="editGoalTarget" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="editGoalTargetDate">Target Completion Date <span class="required">*</span></label>
                <input type="date" name="target_date" id="editGoalTargetDate" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="editGoalNotes">Notes / Description</label>
                <textarea name="notes" id="editGoalNotes" class="form-control" rows="2"></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editGoalModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- CONTRIBUTION HISTORY MODAL -->
<div class="modal-backdrop" id="historyModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title"><i class="bi bi-clock-history"></i> Contribution Log</h3>
            <button type="button" class="btn-close" onclick="closeModal('historyModal')"><i class="bi bi-x"></i></button>
        </div>
        <p style="font-size: 0.9rem; color: var(--text-dark); margin-bottom: 1rem;">
            Savings history for: <strong id="historyGoalTitle" style="color: var(--primary);"></strong>
        </p>
        <div id="historyModalBody" style="max-height: 350px; overflow-y: auto;"></div>
        <div style="margin-top: 1.5rem; text-align: right;">
            <button type="button" class="btn btn-outline" onclick="closeModal('historyModal')">Close</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

