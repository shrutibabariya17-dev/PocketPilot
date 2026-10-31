<?php
// tips.php - Essential Student Money Management & Budgeting Guide
$pageTitle = "Student Money Tips & Financial Guide";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Student Money Tips & Guide</h1>
            <p class="page-subtitle">Actionable strategies, college hacks, and tools to make your pocket money last all semester</p>
        </div>
        <div class="header-actions">
            <a href="budget.php" class="btn btn-primary btn-sm"><i class="bi bi-pie-chart"></i> Open Budget Planner</a>
            <a href="savings.php" class="btn btn-success btn-sm"><i class="bi bi-piggy-bank"></i> Start a Savings Goal</a>
        </div>
    </div>

    <!-- INTERACTIVE STUDENT BUDGET CALCULATOR WIDGET -->
    <div class="card calculator-card" style="margin-bottom: 2.5rem; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <span class="badge badge-success" style="margin-bottom: 0.5rem;"><i class="bi bi-calculator"></i> Interactive Calculator</span>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
                    The 50/30/20 College Budget Calculator
                </h2>
                <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 600px;">
                    Enter your total monthly income or pocket money allowance below to see the recommended breakdown between essential needs, lifestyle wants, and savings.
                </p>
            </div>
            <div style="min-width: 240px;">
                <label class="form-label" for="calcIncomeInput">Monthly Income / Pocket Money</label>
                <div class="input-with-icon">
                    <span class="input-prefix"><?= CURRENCY_SYMBOL ?></span>
                    <input type="number" id="calcIncomeInput" class="form-control" value="6000" min="500" step="100" 
                           style="font-size: 1.25rem; font-weight: 700;">
                </div>
            </div>
        </div>

        <!-- Calculated Splits Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
            <!-- 50% Needs -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; border-top: 4px solid var(--primary);">
                <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">
                    50% Essentials & Needs
                </div>
                <div id="calcNeeds" style="font-size: 1.85rem; font-weight: 800; color: var(--primary); margin-bottom: 0.35rem;">₹3,000.00</div>
                <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                    Canteen meals, dorm rent, transit pass, essential textbooks, mobile recharge, and medical supplies.
                </p>
            </div>

            <!-- 30% Wants -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; border-top: 4px solid var(--warning);">
                <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">
                    30% Lifestyle & Social
                </div>
                <div id="calcWants" style="font-size: 1.85rem; font-weight: 800; color: var(--warning); margin-bottom: 0.35rem;">₹1,800.00</div>
                <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                    Weekend outings, campus coffees, cinema tickets, streaming music, and gaming or social events.
                </p>
            </div>

            <!-- 20% Savings -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; border-top: 4px solid var(--success);">
                <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">
                    20% Future Savings
                </div>
                <div id="calcSavings" style="font-size: 1.85rem; font-weight: 800; color: var(--success); margin-bottom: 0.35rem;">₹1,200.00</div>
                <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                    Emergency buffer fund, saving for a laptop upgrade, semester exam certifications, or post-grad travel.
                </p>
            </div>
        </div>
    </div>

    <!-- CURATED STUDENT MONEY TIPS -->
    <div class="two-col-layout">
        
        <!-- Left: Tips Articles -->
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-dark); margin-bottom: 1.25rem;">
                <i class="bi bi-lightbulb-fill" style="color: var(--warning);"></i> 7 Golden Rules of Student Budgeting
            </h2>

            <!-- Tip 1 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">1</div>
                <div class="tip-content">
                    <h3>Never Buy New Textbooks on Day One</h3>
                    <p>
                        Professors often list recommended textbooks that are rarely needed cover-to-cover. Wait until after the first week of class. When needed, check your campus library reserve desk, look for digital open-access editions (OpenStax), rent via Chegg, or buy used copies from senior students in campus Facebook or WhatsApp groups. You will easily save <strong>₹2,000 to ₹5,000 each semester</strong>.
                    </p>
                </div>
            </div>

            <!-- Tip 2 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">2</div>
                <div class="tip-content">
                    <h3>The 24-Hour Rule for Non-Essential Purchases</h3>
                    <p>
                        Found a stylish pair of shoes, a cool gadget, or a video game online? Force yourself to wait 24 hours before completing checkout. Over 70% of impulse buying urges vanish once the initial dopamine spike settles. If you still need it 24 hours later and it fits your budget, go for it guilt-free.
                    </p>
                </div>
            </div>

            <!-- Tip 3 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">3</div>
                <div class="tip-content">
                    <h3>Maximize Student Discounts with Your .edu Email</h3>
                    <p>
                        Your university email ID is a goldmine. Companies offer massive student pricing:
                    </p>
                    <ul style="margin: 0.5rem 0 0.5rem 1.25rem; font-size: 0.9rem; color: var(--text-body);">
                        <li><strong>Spotify Premium Student:</strong> Half price + Hulu bundle.</li>
                        <li><strong>GitHub Student Developer Pack:</strong> Free domain names, developer tools, and cloud credits.</li>
                        <li><strong>Apple & Dell Education Stores:</strong> Discounted laptops, free AirPods, or trade-in credits.</li>
                        <li><strong>Public Transit:</strong> Student metro rail & bus passes save up to 50% monthly.</li>
                    </ul>
                </div>
            </div>

            <!-- Tip 4 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">4</div>
                <div class="tip-content">
                    <h3>Master Dorm & Shared Meal Prep</h3>
                    <p>
                        Eating out at cafes twice a day costs ₹150 to ₹300 daily (₹4,500+/month). Invest in a basic kettle, microwave, or instant pot. Cooking oats, rice bowls, pasta, and eggs cuts your monthly food expense in half while keeping your energy steady for late-night exams.
                    </p>
                </div>
            </div>

            <!-- Tip 5 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">5</div>
                <div class="tip-content">
                    <h3>Beware of "Buy Now, Pay Later" (BNPL) & High-Interest Cards</h3>
                    <p>
                        Services like Klarna, Afterpay, or store credit cards make large purchases feel small by splitting them into ₹500 payments. But stacking multiple BNPL plans quickly eats up your entire monthly allowance, triggering late penalty fees. If you do not have the money in your account today, do not buy it.
                    </p>
                </div>
            </div>

            <!-- Tip 6 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">6</div>
                <div class="tip-content">
                    <h3>Pick Up Low-Stress Campus Gigs</h3>
                    <p>
                        On-campus jobs (like library desk proctoring, IT helpdesk, or campus gym receptionist) allow you to earn an extra ₹3,000 to ₹8,000 a month while frequently having downtime between tasks to finish your homework and study.
                    </p>
                </div>
            </div>

            <!-- Tip 7 -->
            <div class="tip-article-card">
                <div class="tip-number-badge">7</div>
                <div class="tip-content">
                    <h3>Build a ₹2,500 "Oops" Emergency Cushion First</h3>
                    <p>
                        Before saving for luxury vacations or gaming consoles, stash ₹2,500 in a dedicated PocketPilot savings goal. When your laptop charger breaks or you have an unexpected doctor visit, you won't need to scramble or borrow money from friends.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: Monthly Checklist & Quick Actions -->
        <div>
            <!-- Monthly Student Financial Checklist -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title" style="font-size: 1.1rem;">
                        <i class="bi bi-card-checklist" style="color: var(--primary);"></i> Monthly Money Checklist
                    </h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.88rem;">
                    <label class="checklist-item" style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                        <input type="checkbox" style="margin-top: 0.25rem;">
                        <span>Review bank statements for sneaky recurring subscription charges (gym, apps).</span>
                    </label>
                    <label class="checklist-item" style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                        <input type="checkbox" checked style="margin-top: 0.25rem;">
                        <span>Log all expenses in PocketPilot within 24 hours of purchase.</span>
                    </label>
                    <label class="checklist-item" style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                        <input type="checkbox" style="margin-top: 0.25rem;">
                        <span>Check category budgets before planning weekend dinners out.</span>
                    </label>
                    <label class="checklist-item" style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                        <input type="checkbox" style="margin-top: 0.25rem;">
                        <span>Transfer at least 10% of allowance into your savings goal.</span>
                    </label>
                    <label class="checklist-item" style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;">
                        <input type="checkbox" style="margin-top: 0.25rem;">
                        <span>Sell old semester textbooks or unused electronics for cash.</span>
                    </label>
                </div>
            </div>

            <!-- Fast Action Banner -->
            <div class="card" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">Ready to Put Tips into Practice?</h3>
                <p style="font-size: 0.88rem; opacity: 0.9; margin-bottom: 1.25rem; line-height: 1.5;">
                    The best financial plan is the one you actually use. Log your last transaction right now to build the habit!
                </p>
                <a href="add-expense.php" class="btn btn-success" style="width: 100%;">
                    <i class="bi bi-plus-lg"></i> Log a Transaction Now
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

