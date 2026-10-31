# 🚀 PocketPilot Production Deployment Guide

This guide provides step-by-step instructions to deploy and publish **PocketPilot: Student Financial Co-Pilot** to any live hosting platform.

---

## 📋 Quick Deployment Options

| Hosting Type | Recommended Platforms | Cost | Skill Level | Deployment Method |
|---|---|---|---|---|
| **Free / Cheap Shared Hosting** | [InfinityFree](https://infinityfree.com), [Hostinger](https://hostinger.com), cPanel, Namecheap | Free – \$2/mo | Beginner (Easiest) | Upload `pocketpilot-deploy.zip` via File Manager / FTP |
| **Modern Cloud (Git-based)** | [Railway.app](https://railway.app), [Render.com](https://render.com) | Free tier available | Intermediate | Connect GitHub repo + MySQL plugin |
| **Docker / Cloud VPS** | DigitalOcean, AWS EC2, Linode, Ubuntu VPS | \$4–\$6/mo | Intermediate | `docker compose up -d` |

---

## ⚡ Zero-Configuration Auto-Installer
PocketPilot is engineered with an **automatic database installer**:
When you deploy the site and connect your database credentials, **PocketPilot will automatically create all tables, authentication schemas, and populate the initial Rupee seed data** on your very first page load! You don't even have to manually import `database.sql` unless your host restricts table creation.

### 🔑 Default Student Login Credentials
Anyone visiting your live site can log in immediately:
- **Instant Access**: Click the **"1-Click Student Demo Login"** button on the sign-in page.
- **Manual Credentials**:
  - Email: `student@pocketpilot.com`
  - Password: `password123`
- **Self-Registration**: Anyone can also click **"Create Free Account"** (`register.php`) to sign up with their personal or university email!

---

## 🌐 Option 1: Shared Web Hosting & cPanel (InfinityFree, Hostinger, etc.)
*This is the simplest and most common method for student projects and personal websites.*

### Step 1: Generate the Deployment Zip
In your project folder, double-click **`create_deploy_zip.bat`**. This produces a clean, production-ready package:
`pocketpilot-deploy.zip` (excludes git and local development files).

### Step 2: Upload Files to your Hosting
1. Log in to your hosting control panel (cPanel, DirectAdmin, or InfinityFree Client Area).
2. Open the **File Manager** and navigate into your public web root:
   - For cPanel / Hostinger: `public_html/`
   - For InfinityFree: `htdocs/`
3. Click **Upload** and select `pocketpilot-deploy.zip`.
4. Right-click `pocketpilot-deploy.zip` in File Manager and click **Extract**.
5. Ensure `index.php` is located directly inside `public_html/` (not inside a subfolder).

### Step 3: Create MySQL Database in your Hosting Panel
1. In your hosting panel, open **MySQL Databases** (or **MySQL Database Wizard**).
2. Create a new database name (e.g. `epiz_38291_pocketpilot`).
3. Create a database user and choose a strong password.
4. **Grant All Privileges** to this user for the database.
5. Note the **MySQL Hostname** (on cPanel it's usually `localhost`; on InfinityFree it might look like `sql101.epizy.com`).

### Step 4: Configure Database Credentials
You can configure credentials using either of these methods:

#### Method A: Using a `.env` file (Recommended)
1. Rename `.env.example` in your File Manager to `.env`.
2. Edit `.env` and fill in your hosting credentials:
   ```env
   APP_ENV=production
   APP_TIMEZONE=Asia/Kolkata
   CURRENCY_SYMBOL=₹

   DB_HOST=localhost            # or sqlXXX.yourhost.com
   DB_PORT=3306
   DB_NAME=your_database_name
   DB_USER=your_database_user
   DB_PASS=your_password
   ```

#### Method B: Directly in `config/db.php`
If your host hides dotfiles like `.env`, you can directly edit `config/db.php` on line 21:
```php
$db_host = 'localhost'; // Your host
$db_name = 'your_database_name';
$db_user = 'your_database_user';
$db_pass = 'your_password';
```

### Step 5: (Optional) Manual Database Import via phpMyAdmin
If your host blocks PHP from creating tables automatically:
1. Open **phpMyAdmin** from your hosting control panel.
2. Click on your database in the left sidebar.
3. Click the **Import** tab at the top.
4. Select `database.sql` from your computer and click **Go**.

### Step 6: Visit Your Website!
Navigate to `https://your-domain.com` or `https://yourusername.epizy.com`. Your PocketPilot is live and fully operational!

---

## ☁️ Option 2: Modern Cloud Hosting (Railway / Render via GitHub)

### Step 1: Push Code to GitHub
1. Open terminal / command prompt in your workspace:
   ```bash
   git add .
   git commit -m "Deploy PocketPilot production release"
   ```
2. Create a new repository on [GitHub](https://github.com/new) named `pocketpilot`.
3. Link and push your repository:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/pocketpilot.git
   git branch -M main
   git push -u origin main
   ```

### Step 2: Deploy on Railway (Fastest Cloud Option)
1. Sign up/log in at [Railway.app](https://railway.app).
2. Click **New Project** -> **Provision MySQL**.
3. In the same project, click **New** -> **GitHub Repo** -> select your `pocketpilot` repository.
4. Railway will automatically detect the PHP app or `Dockerfile`.
5. Under the web service **Variables**, add:
   - `DATABASE_URL`: `${{MySQL.DATABASE_URL}}` (Railway links this automatically!)
   - `APP_ENV`: `production`
   - `CURRENCY_SYMBOL`: `₹`
6. Click **Generate Domain** under Settings -> Networking.
7. PocketPilot will automatically connect and create the database schema on launch!

---

## 🐳 Option 3: Docker & Docker Compose (Any VPS / Linux Server)

If you have a Linux VPS (DigitalOcean, Linode, AWS EC2, Hetzner, Oracle Cloud):

1. Clone or copy your project to the server:
   ```bash
   git clone https://github.com/YOUR_USERNAME/pocketpilot.git /var/www/pocketpilot
   cd /var/www/pocketpilot
   ```
2. Launch with Docker Compose:
   ```bash
   docker compose up -d
   ```
3. Docker Compose will automatically:
   - Build the PHP 8.2 Apache container with `pdo_mysql`, `mod_rewrite`, and `opcache`.
   - Spin up a MySQL 8.0 container.
   - Automatically mount and seed `database.sql`.
   - Expose the app on port `8080` (or whichever port you choose in `docker-compose.yml`).
4. Access at `http://your-server-ip:8080`.

---

## 🔒 Production Security Checklist

Before sharing your public URL, verify these items:

- [x] **Database Credentials Protected**: `.htaccess` denies direct web access to `.env`, `.git`, `database.sql`, and batch files.
- [x] **Error Shielding**: `APP_ENV=production` suppresses PHP error backtraces to end-users while writing them to server logs.
- [x] **Security Headers**: `.htaccess` enables `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, and XSS protection.
- [x] **Compression & Caching**: Gzip compression and browser caching rules are configured in `.htaccess` for optimal page speed.
- [x] **Branded 404 Page**: `404.php` gracefully captures missing routes with direct student navigation back to safety.
- [x] **SSL / HTTPS**: Ensure SSL is enabled (cPanel provides free "AutoSSL" via Let's Encrypt).

---

## 🛠️ Common Troubleshooting

### "Database Connection Error"
- Double-check the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `.env` or `config/db.php`.
- In shared hosting (e.g. InfinityFree), `DB_HOST` is usually not `localhost` — copy the exact MySQL hostname from your hosting management panel.
- Ensure the database user has been granted **ALL PRIVILEGES** to the database.

### "404 Not Found on Subpages"
- Ensure Apache `mod_rewrite` is enabled on your host.
- Ensure `.htaccess` is uploaded to the root web folder (some FTP clients hide files starting with a dot `.`).

### "Changes not saving or permission error"
- Ensure the web server has read/write permissions on the application folder (`chmod 755` for directories, `chmod 644` for files).

