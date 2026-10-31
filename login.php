<?php
// login.php - Normal Student Login with 1-Click Instant Access
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$redirect = sanitize($_GET['redirect'] ?? 'dashboard.php');
// Ensure redirect is local and safe
if (empty($redirect) || strpos($redirect, '://') !== false || !preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $redirect)) {
    $redirect = 'dashboard.php';
}

// If already logged in, redirect straight away
if (is_logged_in()) {
    header("Location: " . $redirect);
    exit;
}

$errors = [];
$email = '';

// Handle 1-Click Instant Demo Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'demo_login') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['student@pocketpilot.com']);
    $user = $stmt->fetch();

    if (!$user) {
        $hashed = password_hash('password123', PASSWORD_DEFAULT);
        $stmtIns = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmtIns->execute(['Rahul Sharma', 'student@pocketpilot.com', $hashed]);
        $user = [
            'id' => $pdo->lastInsertId(),
            'name' => 'Rahul Sharma',
            'email' => 'student@pocketpilot.com'
        ];
    }

    login_user($user);
    set_flash('success', "Welcome, <strong>" . htmlspecialchars($user['name']) . "</strong>! Logged in with the student demo account.");
    header("Location: " . $redirect);
    exit;
}

// Handle Normal Email & Password Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'normal_login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Please enter your password.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            login_user($user);
            set_flash('success', "Welcome back, <strong>" . htmlspecialchars($user['name']) . "</strong>!");
            header("Location: " . $redirect);
            exit;
        } else {
            $errors[] = "Invalid email or password. You can also click the 1-Click Demo Login button above to enter immediately!";
        }
    }
}

$pageTitle = "Student Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo-badge">
                    <i class="bi bi-wallet2"></i>
                </div>
                <h1 class="auth-title">Student Sign In</h1>
                <p class="auth-subtitle">Log in to track your expenses, pocket money & savings</p>
            </div>

            <!-- Instant 1-Click Access Box (Anyone Can Log In) -->
            <div class="auth-demo-banner">
                <div class="auth-demo-badge">
                    <i class="bi bi-lightning-charge-fill"></i> Instant Access
                </div>
                <div class="auth-demo-text">Anyone can sign in with 1 click (no password needed):</div>
                <form method="POST" action="login.php?redirect=<?= urlencode($redirect) ?>">
                    <input type="hidden" name="action" value="demo_login">
                    <button type="submit" class="btn btn-demo btn-block">
                        <i class="bi bi-box-arrow-in-right"></i> 1-Click Student Demo Login
                    </button>
                </form>
                <div class="auth-demo-creds">
                    <span>Email: <code>student@pocketpilot.com</code></span>
                    <span>Pass: <code>password123</code></span>
                    <button type="button" class="btn-fill-creds" onclick="fillDemoCreds()">Auto-Fill Form</button>
                </div>
            </div>

            <div class="auth-divider">
                <span>Or sign in with email</span>
            </div>

            <!-- Server-Side Error Notifications -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                    <div class="alert-content">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div>
                            <?php foreach ($errors as $err): ?>
                                <div><?= htmlspecialchars($err) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Normal Login Form -->
            <form method="POST" action="login.php?redirect=<?= urlencode($redirect) ?>" class="auth-form" id="loginForm">
                <input type="hidden" name="action" value="normal_login">

                <div class="form-group">
                    <label for="loginEmail" class="form-label">Email Address <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="loginEmail" name="email" class="form-control" 
                               placeholder="e.g. student@pocketpilot.com" 
                               value="<?= htmlspecialchars($email) ?>" required autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label for="loginPassword" class="form-label" style="margin-bottom: 0;">Password <span class="required">*</span></label>
                        <button type="button" class="btn-fill-creds" onclick="togglePasswordVisibility()" id="togglePwdBtn" style="font-size: 0.78rem;">
                            <i class="bi bi-eye"></i> Show
                        </button>
                    </div>
                    <div class="input-with-icon">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="loginPassword" name="password" class="form-control" 
                               placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>

                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; color: var(--text-dark); cursor: pointer; user-select: none;">
                        <input type="checkbox" name="remember" value="1" checked style="accent-color: var(--primary); width: 16px; height: 16px;">
                        Remember me on this browser
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 1rem; font-weight: 700;">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In to Account
                </button>
            </form>

            <div class="auth-footer-links">
                <p>Don't have an account yet? <a href="register.php?redirect=<?= urlencode($redirect) ?>">Create a Free Student Account</a></p>
                <p style="margin-top: 0.5rem;"><a href="index.php" style="color: var(--text-muted); font-size: 0.84rem;"><i class="bi bi-arrow-left"></i> Back to PocketPilot Home</a></p>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemoCreds() {
    document.getElementById('loginEmail').value = 'student@pocketpilot.com';
    document.getElementById('loginPassword').value = 'password123';
    document.getElementById('loginPassword').focus();
}

function togglePasswordVisibility() {
    const pwdInput = document.getElementById('loginPassword');
    const toggleBtn = document.getElementById('togglePwdBtn');
    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        toggleBtn.innerHTML = '<i class="bi bi-eye-slash"></i> Hide';
    } else {
        pwdInput.type = 'password';
        toggleBtn.innerHTML = '<i class="bi bi-eye"></i> Show';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
