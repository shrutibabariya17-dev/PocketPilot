<?php
// 404.php - PocketPilot Custom 404 Page
http_response_code(404);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Page Not Found (404)";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 720px; margin: 4rem auto; text-align: center; padding: 2rem 1rem;">
    <div class="card" style="padding: 3rem 2rem; border-radius: 1.25rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);">
        <div style="width: 80px; height: 80px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 2.25rem; margin-bottom: 1.5rem;">
            <i class="bi bi-compass"></i>
        </div>

        <h1 style="font-size: 2.25rem; font-weight: 800; color: #1e293b; margin-bottom: 0.75rem;">404 - Page Not Found</h1>
        <p style="font-size: 1.05rem; color: #64748b; max-width: 500px; margin: 0 auto 2rem; line-height: 1.6;">
            Looks like your financial co-pilot lost the coordinates for this page. Don't worry, your money and transactions are safe!
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="dashboard.php" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-weight: 600;">
                <i class="bi bi-speedometer2"></i> Back to Dashboard
            </a>
            <a href="index.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; font-weight: 600;">
                <i class="bi bi-house-door"></i> Home
            </a>
            <a href="transactions.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; font-weight: 600;">
                <i class="bi bi-receipt"></i> View Transactions
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
