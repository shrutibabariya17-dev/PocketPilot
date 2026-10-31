# PocketPilot — Student Financial Co-Pilot 🎓✈️

PocketPilot is a modern, responsive personal finance and budgeting web application tailored specifically for college, university, and high-school students. Built with **HTML5, CSS3, JavaScript, PHP 8, and MySQL**.

---

## 🌟 The 10 Website Pages

1. **Home (`index.php`)**: Introduction to PocketPilot, hero section with live stats preview, features, student benefits, 3-step guide, and "Get Started" call to action.
2. **Dashboard (`dashboard.php`)**: Real-time financial hub displaying total income, total expenses, available balance, budget health, recent transactions, monthly spending breakdown, and spending pace recommendations.
3. **Add Expense (`add-expense.php`)**: Fast logging form for student outflows with amount presets (`₹50 Chai`, `₹150 Meal`, `₹400 Books`), category selection, date, payment method, notes, and validation.
4. **Income Tracker (`income.php`)**: Dedicated inflow tracking for pocket money allowances, semester scholarships, campus library shifts, freelance tutoring, and gifts, complete with source distribution metrics.
5. **Transaction History (`transactions.php`)**: Full transaction audit table with real-time keyword search, type filter (Income/Expense), category filter, date range filters, modal editing, and safe deletion.
6. **Budget Planner (`budget.php`)**: Set monthly category limits, visualize progress bars, and receive smart color-coded warning banners when nearing or exceeding 80%/100% of a budget.
7. **Savings Goals (`savings.php`)**: Define milestone goals (laptop, emergency cushion, trip), record incremental deposits, track days left, and celebrate 100% completion.
8. **Reports & Insights (`reports.php`)**: Visual analytics including a 6-month comparative income vs. expense CSS bar chart, category expense share, payment mode distribution, savings rate, and print-ready layout.
9. **Student Money Tips (`tips.php`)**: Curated college financial hacks, the 50/30/20 rule, campus discounts guide, meal prep strategies, monthly financial checklist, and an **interactive JavaScript budget calculator**.
10. **About & Contact (`about.php`)**: Mission statement, technology stack breakdown (HTML5, CSS3, JS, PHP, MySQL), project FAQ, and a **fully functional contact form with server-side validation** saving messages to the database.

### 🔐 Authentication System (Login & Registration)
- **Student Login (`login.php`)**: Secure sign-in supporting both standard email/password authentication and **1-Click Instant Demo Access** so anyone can test and experience the full application in one click.
- **Student Sign Up (`register.php`)**: Full registration with secure password hashing (`bcrypt`), email uniqueness validation, and instant session start.
- **Sign Out (`logout.php`)**: Safe session cleanup and notification.
- **Default Demo Credentials**:
  - Email: `student@pocketpilot.com`
  - Password: `password123`
  - *(Or simply click the "1-Click Student Demo Login" button on `login.php`)*

---

## 🚀 How to Run Locally

### Option A: One-Click Launcher (Easiest)
Simply double-click **`PocketPilot.bat`** (or `start_pocketpilot.bat`) in the project folder. It will automatically:
1. Verify MySQL is running.
2. Start the lightweight PHP server.
3. Open your browser directly to `http://localhost:8080/index.php`.

### Option B: Using XAMPP
1. Ensure **Apache** and **MySQL** are running in the **XAMPP Control Panel**.
2. Visit `http://localhost:8080/index.php` or copy the folder into `htdocs` and visit `http://localhost/pr1/index.php`.
3. PocketPilot automatically initializes the database tables and starter data on first visit!

---

## 🌐 How to Deploy and Publish Online

Full production deployment instructions are documented in [**`DEPLOYMENT.md`**](file:///d:/shruti'/pr1/DEPLOYMENT.md).

### Quick 3-Step Upload (Shared Hosting / cPanel / InfinityFree):
1. Run **`create_deploy_zip.bat`** in your project folder to generate `pocketpilot-deploy.zip`.
2. Upload and extract `pocketpilot-deploy.zip` inside `public_html/` (or `htdocs/`) via your hosting File Manager.
3. Configure your database credentials in `.env` (or `config/db.php`).
4. PocketPilot will auto-initialize tables and seed data upon opening your live URL!

### Cloud Deploy (Railway / Render):
1. Push this folder to a GitHub repository (`git push origin main`).
2. Link the repository on [Railway](https://railway.app) or [Render](https://render.com).
3. Connect a MySQL database and set `DATABASE_URL`. Railway/Render will automatically build and publish using the provided `Dockerfile` / `Procfile`.

### Docker (Linux VPS):
Run `docker compose up -d` to launch the containerized PHP Apache + MySQL 8.0 cluster.

---

## 📂 Project Architecture

```
d:\shruti'\pr1/
├── config/
│   └── db.php                  # PDO database connection & auto-installer (env aware)
├── includes/
│   ├── header.php              # Global navigation bar & meta header
│   ├── footer.php              # Responsive footer & scripts
│   └── functions.php           # Sanitization, currency formatting & data helpers
├── css/
│   └── style.css               # Modern, responsive mobile-first CSS design system
├── js/
│   ├── main.js                 # Mobile menu toggle, modals, presets & alerts
│   └── charts.js               # Visual chart and progress bar animations
├── database.sql                # MySQL schema and starter student data
├── .env.example                # Deployment environment variable template
├── .htaccess                   # Apache security, compression & caching rules
├── 404.php                     # Branded student 404 error page
├── Dockerfile                  # Container definition for cloud VPS
├── docker-compose.yml          # Multi-container orchestration (PHP + MySQL 8)
├── Procfile                    # PaaS process file (Heroku/Render)
├── create_deploy_zip.bat       # One-click deployment zip builder
├── PocketPilot.bat             # One-click local desktop launcher
├── DEPLOYMENT.md               # Complete publishing & hosting manual
├── index.php                   # 1. Home Page
├── dashboard.php               # 2. Dashboard Hub
├── add-expense.php             # 3. Add Expense Form
├── income.php                  # 4. Income Tracker
├── transactions.php            # 5. Transactions History
├── budget.php                  # 6. Budget Planner & Alerts
├── savings.php                 # 7. Savings Goals
├── reports.php                 # 8. Reports & Analytics
├── tips.php                    # 9. Student Money Tips & Calculator
└── about.php                   # 10. About & Contact Form
```

---

## 🌐 Deploy to Vercel (1-Click)

PocketPilot includes native **Vercel** serverless configuration (`vercel.json` + `api/index.php` front-controller):

1. Push to your GitHub repository: `https://github.com/shrutibabariya17-dev/PocketPilot`
2. Go to **[vercel.com](https://vercel.com)** &rarr; **Add New Project** &rarr; **Import `PocketPilot`**.
3. Under Project Settings &rarr; **Environment Variables**, add your cloud MySQL credentials:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (or `DATABASE_URL`) from any free MySQL provider (TiDB Cloud, Aiven, or Railway).
4. Click **Deploy**! PocketPilot automatically initializes your tables on first visit.
5. See [`VERCEL_DEPLOYMENT.md`](file:///d:/shruti'/pr1/VERCEL_DEPLOYMENT.md) for detailed instructions.

---

## 🔒 Production Features Included

- **Indian Rupee (`₹`) Everywhere**: Formatted with standard grouping and realistic student figures.
- **Output Buffering & Zero Warning Redirects**: Solves all "headers already sent" issues across all PHP environments.
- **Auto-Schema Migration**: Tables and initial data auto-load on first boot if missing.
- **Apache Hardening (`.htaccess`)**: Blocks access to `.env`, `.sql`, `.git`, `.bat`; enables Gzip compression and browser caching.
- **Responsive Navigation**: Mobile hamburger drawer with touch support and desktop responsive navigation.
