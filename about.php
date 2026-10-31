<?php
// about.php - About PocketPilot & Functional Contact Form
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$name = '';
$email = '';
$rawEmail = '';
$subject = 'General Feedback';
$message = '';
$formSubmitted = false;

// Handle Contact Form Submission with Server-Side Validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_contact') {
    $name = sanitize($_POST['name'] ?? '');
    $rawEmail = sanitize($_POST['email'] ?? '');
    $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    // Server-side validation rules
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Please provide your full name (minimum 2 characters).";
    }
    if (!$email) {
        $errors[] = "Please provide a valid email address so we can reply back to you.";
    }
    if (empty($subject)) {
        $errors[] = "Please select or enter a message subject.";
    }
    if (empty($message) || strlen($message) < 10) {
        $errors[] = "Your message is too short. Please provide at least 10 characters.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $subject, $message]);

            $formSubmitted = true;
            set_flash('success', "Thank you, <strong>" . htmlspecialchars($name) . "</strong>! Your message has been received. Our student support team will reply to <em>" . htmlspecialchars($email) . "</em> shortly.");
            
            // Clear form inputs
            $name = '';
            $email = '';
            $rawEmail = '';
            $subject = 'General Feedback';
            $message = '';
        } catch (PDOException $e) {
            $errors[] = "Failed to record message due to database error: " . $e->getMessage();
        }
    }
}

// HTML Layout Starts Here
$pageTitle = "About PocketPilot & Contact";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">About PocketPilot & Contact</h1>
            <p class="page-subtitle">Learn about our mission to democratize student personal finance, and reach out to us</p>
        </div>
        <div class="header-actions">
            <a href="index.php" class="btn btn-outline btn-sm"><i class="bi bi-house"></i> Home</a>
            <a href="dashboard.php" class="btn btn-primary btn-sm"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div class="alert-content">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div>
                    <strong>Please resolve the following:</strong>
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

    <!-- 2 COLUMN LAYOUT: ABOUT STORY & CONTACT FORM -->
    <div class="two-col-equal-layout" style="margin-bottom: 3rem;">
        
        <!-- Left: Project Purpose & Tech Stack -->
        <div>
            <!-- About Story Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-compass-fill"></i> Project Purpose & Mission</h2>
                    <span class="badge badge-success">Open Student Initiative</span>
                </div>
                
                <p style="color: var(--text-body); font-size: 0.95rem; line-height: 1.7; margin-bottom: 1rem;">
                    <strong>PocketPilot</strong> was conceived out of a real problem faced by nearly every college student: the mid-semester money crunch. Traditional personal finance software is bloated with 401(k) retirement tracking, mortgage amortizations, and credit card debt consolidation that make no sense for undergraduates and scholars living on allowances, part-time wages, and academic scholarships.
                </p>

                <p style="color: var(--text-body); font-size: 0.95rem; line-height: 1.7; margin-bottom: 1rem;">
                    PocketPilot replaces spreadsheets and mental math with an easy, frictionless companion. Students can log lunch purchases in three clicks, see exact budget utilization in real-time, get notified before overspending on weekend outings, and set concrete savings targets for high-impact milestones like new laptops or summer internships.
                </p>

                <div style="background: var(--primary-light); padding: 1.25rem; border-radius: var(--radius-md); border-left: 4px solid var(--primary); margin-top: 1.25rem;">
                    <h4 style="color: var(--primary); font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">Our Core Principles</h4>
                    <p style="font-size: 0.88rem; color: var(--text-dark); margin: 0;">
                        1. <strong>Simplicity First:</strong> No Wall Street jargon.<br>
                        2. <strong>Zero Data Snooping:</strong> Everything stays stored in your local MySQL database.<br>
                        3. <strong>Habit Over Perfection:</strong> Encouraging daily tracking over complicated calculations.
                    </p>
                </div>
            </div>

            <!-- Technologies Used Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="bi bi-code-slash"></i> Technologies Used</h2>
                    <span class="badge badge-category">Full-Stack Architecture</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #ffffff;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <i class="bi bi-filetype-html" style="font-size: 1.4rem; color: #e44d26;"></i>
                            <strong style="color: var(--text-dark);">HTML5</strong>
                        </div>
                        <p style="font-size: 0.82rem; color: var(--text-muted);">
                            Semantic layout, accessible form validation, clean responsive structure.
                        </p>
                    </div>

                    <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #ffffff;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <i class="bi bi-filetype-css" style="font-size: 1.4rem; color: #264de4;"></i>
                            <strong style="color: var(--text-dark);">CSS3</strong>
                        </div>
                        <p style="font-size: 0.82rem; color: var(--text-muted);">
                            Modern CSS variables, flexbox & grid design system, smooth progress animations.
                        </p>
                    </div>

                    <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #ffffff;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <i class="bi bi-filetype-js" style="font-size: 1.4rem; color: #f7df1e;"></i>
                            <strong style="color: var(--text-dark);">JavaScript</strong>
                        </div>
                        <p style="font-size: 0.82rem; color: var(--text-muted);">
                            ES6+, interactive budget calculator, preset pills, modal windows, mobile drawer.
                        </p>
                    </div>

                    <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #ffffff;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <i class="bi bi-filetype-php" style="font-size: 1.4rem; color: #777bb4;"></i>
                            <strong style="color: var(--text-dark);">PHP 8</strong>
                        </div>
                        <p style="font-size: 0.82rem; color: var(--text-muted);">
                            Dynamic server-side rendering, input sanitization, secure sessions, PDO transactions.
                        </p>
                    </div>

                    <div style="padding: 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: #ffffff; grid-column: 1 / -1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <i class="bi bi-database-fill-gear" style="font-size: 1.4rem; color: #00758f;"></i>
                            <strong style="color: var(--text-dark);">MySQL Relational Database</strong>
                        </div>
                        <p style="font-size: 0.82rem; color: var(--text-muted);">
                            Normalized schema with 6 tables (`transactions`, `categories`, `budgets`, `savings_goals`, `savings_contributions`, `contact_messages`), cascading foreign keys, and fast aggregated queries.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Functional Contact Form -->
        <div>
            <div class="card" id="contactSection">
                <div class="card-header">
                    <div>
                        <h2 class="card-title"><i class="bi bi-envelope-paper-fill" style="color: var(--primary);"></i> Get in Touch</h2>
                        <p class="card-subtitle">Questions, student feature suggestions, or bug reports</p>
                    </div>
                </div>

                <form action="about.php#contactSection" method="POST" id="contactForm">
                    <input type="hidden" name="action" value="submit_contact">

                    <div class="form-group">
                        <label class="form-label" for="contactName">Your Name <span class="required">*</span></label>
                        <input type="text" name="name" id="contactName" class="form-control" 
                               placeholder="e.g. Alex Morgan" value="<?= htmlspecialchars($name) ?>" required minlength="2">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactEmail">Email Address <span class="required">*</span></label>
                        <input type="email" name="email" id="contactEmail" class="form-control" 
                               placeholder="alex@university.edu" value="<?= htmlspecialchars($rawEmail ?: (string)$email) ?>" required>
                        <span class="form-hint">We will only use this to respond to your inquiry</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactSubject">Subject <span class="required">*</span></label>
                        <select name="subject" id="contactSubject" class="form-control" required>
                            <option value="General Feedback" <?= $subject === 'General Feedback' ? 'selected' : '' ?>>General Feedback & Thoughts</option>
                            <option value="Feature Request" <?= $subject === 'Feature Request' ? 'selected' : '' ?>>Student Feature Request</option>
                            <option value="Bug Report" <?= $subject === 'Bug Report' ? 'selected' : '' ?>>Bug / Issue Report</option>
                            <option value="Campus Partnership" <?= $subject === 'Campus Partnership' ? 'selected' : '' ?>>Campus / Club Collaboration</option>
                            <option value="Other" <?= $subject === 'Other' ? 'selected' : '' ?>>Other Question</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactMessage">Message <span class="required">*</span></label>
                        <textarea name="message" id="contactMessage" class="form-control" rows="5" 
                                  placeholder="How can we help? Share your feedback or ideas for PocketPilot..." 
                                  required minlength="10"><?= htmlspecialchars($message) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="bi bi-send-fill"></i> Send Message
                    </button>
                </form>

                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.4rem;">
                    <span><i class="bi bi-clock"></i> Typical response time: within 24 hours</span>
                    <span><i class="bi bi-shield-check"></i> Server-side sanitization and validation enabled</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ SECTION -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title"><i class="bi bi-question-circle-fill"></i> Frequently Asked Questions (FAQ)</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <div>
                <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">
                    Is PocketPilot free to use?
                </h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                    Yes, 100% free and open-source for all students. There are no subscriptions, ads, or premium tiers.
                </p>
            </div>

            <div>
                <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">
                    Where is my financial data stored?
                </h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                    All records are stored locally in your dedicated MySQL database (`pocketpilot_db`). No external cloud has access to your numbers.
                </p>
            </div>

            <div>
                <h4 style="font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.35rem;">
                    Can I export my transactions for Excel?
                </h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                    Yes! You can view and print reports directly or query the MySQL database to export CSV spreadsheets whenever you like.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

