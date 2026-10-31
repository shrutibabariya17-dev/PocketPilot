<?php
// register.php - New Student Account Registration
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$redirect = sanitize($_GET['redirect'] ?? 'dashboard.php');
if (empty($redirect) || strpos($redirect, '://') !== false || !preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $redirect)) {
    $redirect = 'dashboard.php';
}

if (is_logged_in()) {
    header("Location: " . $redirect);
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Please enter your full name (at least 2 characters).";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid student or personal email address.";
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match. Please verify both password fields.";
    }

    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists. <a href='login.php?redirect=" . urlencode($redirect) . "'>Sign in here</a>.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, password)
                    VALUES (?, ?, ?)
                ");
                $stmtInsert->execute([$name, $email, $hashed]);

                $newUser = [
                    'id' => $pdo->lastInsertId(),
                    'name' => $name,
                    'email' => $email
                ];

                login_user($newUser);
                set_flash('success', "Welcome to PocketPilot, <strong>" . htmlspecialchars($name) . "</strong>! Your account has been created.");
                header("Location: " . $redirect);
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

$pageTitle = "Create Student Account";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo-badge">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h1 class="auth-title">Create Free Account</h1>
                <p class="auth-subtitle">Join PocketPilot to master your student budget & savings</p>
            </div>

            <!-- Server-Side Error Notifications -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                    <div class="alert-content">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div>
                            <?php foreach ($errors as $err): ?>
                                <div><?= $err ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="register.php?redirect=<?= urlencode($redirect) ?>" class="auth-form">
                <div class="form-group">
                    <label for="regName" class="form-label">Full Name <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" id="regName" name="name" class="form-control" 
                               placeholder="e.g. Priya Sharma" 
                               value="<?= htmlspecialchars($name) ?>" required autocomplete="name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="regEmail" class="form-label">Student / Personal Email <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="regEmail" name="email" class="form-control" 
                               placeholder="e.g. priya@college.edu" 
                               value="<?= htmlspecialchars($email) ?>" required autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="regPassword" class="form-label">Create Password <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="regPassword" name="password" class="form-control" 
                               placeholder="At least 6 characters" required autocomplete="new-password">
                    </div>
                    <span class="form-hint">Must be at least 6 characters long</span>
                </div>

                <div class="form-group">
                    <label for="regConfirmPassword" class="form-label">Confirm Password <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-shield-check input-icon"></i>
                        <input type="password" id="regConfirmPassword" name="confirm_password" class="form-control" 
                               placeholder="Re-type your password" required autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 1rem; font-weight: 700;">
                    <i class="bi bi-check-circle"></i> Create Account & Start
                </button>
            </form>

            <div class="auth-divider">
                <span>Or jump straight in</span>
            </div>

            <form method="POST" action="login.php?redirect=<?= urlencode($redirect) ?>">
                <input type="hidden" name="action" value="demo_login">
                <button type="submit" class="btn btn-demo btn-block">
                    <i class="bi bi-lightning-charge-fill"></i> 1-Click Instant Demo Login
                </button>
            </form>

            <div class="auth-footer-links">
                <p>Already have an account? <a href="login.php?redirect=<?= urlencode($redirect) ?>">Sign in here</a></p>
                <p style="margin-top: 0.5rem;"><a href="index.php" style="color: var(--text-muted); font-size: 0.84rem;"><i class="bi bi-arrow-left"></i> Back to PocketPilot Home</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

