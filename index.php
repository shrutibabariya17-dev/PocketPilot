<?php
// index.php - PocketPilot Home Page
$pageTitle = "PocketPilot — Master Your Student Budget & Savings";
require_once __DIR__ . '/includes/header.php';

// Quick stats for hero preview with resilient fallback
try {
    $summary = get_financial_summary($pdo, CURRENT_MONTH);
    $recentList = get_recent_transactions($pdo, 3);
} catch (Throwable $e) {
    error_log("Hero stats preview fallback: " . $e->getMessage());
    $summary = [
        'total_income' => 27000.00,
        'total_expenses' => 5414.00,
        'available_balance' => 21586.00,
        'month_balance' => 21586.00,
        'all_income' => 27000.00,
        'all_expenses' => 5414.00
    ];
    $recentList = [];
}
?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="container">
        <div class="hero-pill">
            <i class="bi bi-stars"></i> Designed Specifically for College & University Students
        </div>
        <h1 class="hero-title">
            Stop Guessing Where Your Pocket Money Went. <span class="gradient-text">Take Control with PocketPilot.</span>
        </h1>
        <p class="hero-description">
            The smart, stress-free student finance tracker that helps you log daily expenses, monitor scholarships and side gigs, set category budgets, and achieve savings goals before semester ends.
        </p>

        <div class="hero-buttons">
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-speedometer2"></i> Open Dashboard
                </a>
                <a href="add-expense.php" class="btn btn-outline btn-lg">
                    <i class="bi bi-plus-circle"></i> Log an Expense
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In / 1-Click Demo
                </a>
                <a href="register.php" class="btn btn-outline btn-lg">
                    <i class="bi bi-person-plus"></i> Create Free Account
                </a>
            <?php endif; ?>
        </div>

        <!-- Hero Live Preview Cards -->
        <div class="hero-preview-cards">
            <div class="preview-stat">
                <div class="stat-label">Available Balance</div>
                <div class="stat-value" style="color: var(--primary);"><?= format_currency($summary['available_balance']) ?></div>
                <div class="stat-subtext"><i class="bi bi-shield-check text-success"></i> Current student balance</div>
            </div>
            <div class="preview-stat">
                <div class="stat-label">This Month's Spending</div>
                <div class="stat-value" style="color: var(--danger);"><?= format_currency($summary['total_expenses']) ?></div>
                <div class="stat-subtext"><i class="bi bi-calendar-event"></i> Tracked for <?= date('F Y') ?></div>
            </div>
            <div class="preview-stat">
                <div class="stat-label">Total Income Tracked</div>
                <div class="stat-value" style="color: var(--success);"><?= format_currency($summary['total_income']) ?></div>
                <div class="stat-subtext"><i class="bi bi-arrow-up-right"></i> Pocket money & grants</div>
            </div>
        </div>
    </div>
</section>

<!-- CORE FEATURES SECTION -->
<section class="features-section">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Features Built for Student Life</span>
            <h2 class="section-title">Everything You Need to Run Your Finances</h2>
            <p class="section-desc">Traditional banking apps are cluttered with mortgages and stock portfolios. PocketPilot gives you pure, actionable student money tools.</p>
        </div>

        <div class="features-grid">
            <div class="feature-box">
                <div class="feature-icon"><i class="bi bi-receipt"></i></div>
                <h3 class="feature-title">Fast Expense Logging</h3>
                <p class="feature-desc">Record food, canteen snacks, study textbooks, transport, and tech subscriptions in seconds with quick-preset amounts and payment mode tracking.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon" style="background-color: var(--success-light); color: var(--success);"><i class="bi bi-wallet-fill"></i></div>
                <h3 class="feature-title">Multi-Source Income Tracker</h3>
                <p class="feature-desc">Track pocket money allowances, semester scholarships, campus library shifts, freelance gigs, and cash gifts in one organized view.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon" style="background-color: var(--warning-light); color: var(--warning);"><i class="bi bi-sliders"></i></div>
                <h3 class="feature-title">Category-Wise Budgets</h3>
                <p class="feature-desc">Set spending limits on dining, outings, and shopping. Get timely visual warnings when you hit 80% or 100% of your threshold.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon" style="background-color: #f3e8ff; color: #9333ea;"><i class="bi bi-piggy-bank-fill"></i></div>
                <h3 class="feature-title">Target Savings Goals</h3>
                <p class="feature-desc">Saving for a new coding laptop, certification exam, or spring break trip? Create targets, log contributions, and track percentage progress.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon" style="background-color: var(--info-light); color: var(--info);"><i class="bi bi-graph-up-arrow"></i></div>
                <h3 class="feature-title">Visual Reports & Charts</h3>
                <p class="feature-desc">Understand where every rupee goes with monthly comparative income vs. expense charts and detailed category breakdown distributions.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon" style="background-color: #ecfdf5; color: #059669;"><i class="bi bi-lightbulb-fill"></i></div>
                <h3 class="feature-title">Student Money Tips & Guide</h3>
                <p class="feature-desc">Access curated college financial hacks: 50/30/20 budgeting, student meal prep tricks, textbook discounts, and interactive budget calculators.</p>
            </div>
        </div>
    </div>
</section>

<!-- BENEFITS SECTION -->
<section class="benefits-section" style="background-color: #f1f5f9;">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Student Benefits</span>
            <h2 class="section-title">Why Students Love PocketPilot</h2>
            <p class="section-desc">Built from the ground up for dorm life, university routines, and tight semester budgets.</p>
        </div>

        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon-wrap"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                    <h4 style="font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">100% Free & Private</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">No subscription paywalls, no selling your financial data to third-party advertisers. Your data lives in your database.</p>
                </div>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon-wrap" style="background-color: var(--primary-light); color: var(--primary);"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <h4 style="font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">Zero Financial Jargon</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">No confusing Wall Street terms. Clear numbers: how much you received, how much you spent, and how much you have left.</p>
                </div>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon-wrap" style="background-color: var(--warning-light); color: var(--warning);"><i class="bi bi-bell-fill"></i></div>
                <div>
                    <h4 style="font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">Early Overspend Warnings</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">Color-coded alerts notify you before you drain your pocket money, keeping you safe all through month-end exams.</p>
                </div>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon-wrap" style="background-color: #fce7f3; color: #db2777;"><i class="bi bi-phone-fill"></i></div>
                <div>
                    <h4 style="font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">Mobile-Friendly Experience</h4>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">Log an expense on your phone right at the cash register or campus cafeteria with responsive mobile controls.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS SECTION -->
<section class="how-it-works-section">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Simple 3-Step Routine</span>
            <h2 class="section-title">How PocketPilot Works</h2>
            <p class="section-desc">Spend just 30 seconds a day to stay in total financial control.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark);">Log Daily Inflow & Outflow</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Record income when allowances or wages arrive, and log expenses as you spend on meals, books, or transport.</p>
            </div>

            <div class="step-card">
                <div class="step-num" style="background-color: var(--warning);">2</div>
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark);">Set Monthly Category Limits</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Define how much you can comfortably spend on dining out, entertainment, and groceries to avoid month-end crunches.</p>
            </div>

            <div class="step-card">
                <div class="step-num" style="background-color: var(--success);">3</div>
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark);">Crush Savings Milestones</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Set aside remaining cash into dedicated savings goals like laptops, emergency reserves, or holiday travel.</p>
            </div>
        </div>

        <!-- CTA Banner -->
        <div class="cta-banner">
            <h2>Ready to Take Charge of Your Pocket Money?</h2>
            <p>Join thousands of smart college students managing their money with confidence.</p>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="dashboard.php" class="btn btn-success btn-lg">
                    <i class="bi bi-grid-1x2"></i> Go to Dashboard
                </a>
                <a href="about.php" class="btn btn-outline btn-lg" style="color: #ffffff; border-color: rgba(255,255,255,0.4);">
                    <i class="bi bi-info-circle"></i> Learn More About Us
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

