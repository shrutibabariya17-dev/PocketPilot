    </main>
    <!-- Main Page Content Ends -->

    <!-- FOOTER -->
    <?php if (in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])): ?>
    <footer class="site-footer auth-footer-simple">
        <div class="container footer-bottom-flex" style="justify-content: center; text-align: center; padding: 1.5rem 1rem;">
            <p style="font-size: 0.85rem; color: #94a3b8;">&copy; <?= date('Y') ?> PocketPilot Student Finance &bull; Secure Student Authentication</p>
        </div>
    </footer>
    <?php else: ?>
    <footer class="site-footer">
        <div class="footer-top">
            <div class="container footer-grid">
                <!-- Col 1: Brand Info -->
                <div class="footer-brand">
                    <div class="brand-logo footer-logo">
                        <div class="logo-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="brand-text">
                            <span class="brand-name">Pocket<strong>Pilot</strong></span>
                            <span class="brand-badge">Student Edition</span>
                        </div>
                    </div>
                    <p class="footer-desc">
                        PocketPilot is an open, intuitive personal finance and budgeting companion engineered specifically for high school, college, and university students. Take charge of your pocket money, scholarships, and savings with zero stress.
                    </p>
                </div>

                <!-- Col 2: Navigation Links -->
                <div class="footer-links-col">
                    <h5 class="footer-heading">Navigation</h5>
                    <ul class="footer-nav-list">
                        <li><a href="index.php"><i class="bi bi-chevron-right"></i> Home</a></li>
                        <li><a href="dashboard.php"><i class="bi bi-chevron-right"></i> Dashboard</a></li>
                        <li><a href="add-expense.php"><i class="bi bi-chevron-right"></i> Add Expense</a></li>
                        <li><a href="income.php"><i class="bi bi-chevron-right"></i> Income Tracker</a></li>
                        <li><a href="transactions.php"><i class="bi bi-chevron-right"></i> Transactions</a></li>
                    </ul>
                </div>

                <!-- Col 3: Planning & Insights -->
                <div class="footer-links-col">
                    <h5 class="footer-heading">Planning & Tools</h5>
                    <ul class="footer-nav-list">
                        <li><a href="budget.php"><i class="bi bi-chevron-right"></i> Budget Planner</a></li>
                        <li><a href="savings.php"><i class="bi bi-chevron-right"></i> Savings Goals</a></li>
                        <li><a href="reports.php"><i class="bi bi-chevron-right"></i> Financial Reports</a></li>
                        <li><a href="tips.php"><i class="bi bi-chevron-right"></i> Student Money Tips</a></li>
                        <li><a href="about.php"><i class="bi bi-chevron-right"></i> About & Contact</a></li>
                    </ul>
                </div>

                <!-- Col 4: Quick Money Tip Box -->
                <div class="footer-tip-col">
                    <h5 class="footer-heading">Student Finance Rule #1</h5>
                    <div class="tip-card-mini">
                        <i class="bi bi-lightbulb-fill tip-icon"></i>
                        <p class="tip-text">
                            "Pay your savings first before spending on weekend takeout. Even saving just ₹100 or ₹200 a week builds an emergency fund by graduation!"
                        </p>
                        <a href="tips.php" class="btn btn-outline btn-xs">Read 15+ More Tips &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-flex">
                <p>&copy; <?= date('Y') ?> PocketPilot Student Finance. Built with HTML5, CSS3, JavaScript, PHP & MySQL.</p>
                <div class="footer-bottom-links">
                    <a href="index.php">Home</a> &bull;
                    <a href="dashboard.php">Dashboard</a> &bull;
                    <a href="budget.php">Budget</a> &bull;
                    <a href="about.php">About</a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- JavaScript Files -->
    <script src="js/main.js"></script>
    <script src="js/charts.js"></script>
</body>
</html>

