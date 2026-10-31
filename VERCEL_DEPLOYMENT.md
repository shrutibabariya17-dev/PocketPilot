# 🚀 Deploying PocketPilot to Vercel

PocketPilot is fully configured for seamless, zero-config deployment to **Vercel** using the community serverless PHP runtime (`vercel-php@0.9.0`), automatic URL rewriting, and serverless session persistence.

---

## ⚡ Quick Deployment Guide (2 Minutes via GitHub)

### Step 1: Connect Your GitHub Repository to Vercel
1. Log in or sign up at **[vercel.com](https://vercel.com)**.
2. On your Vercel Dashboard, click **Add New…** &rarr; **Project**.
3. Under **Import Git Repository**, find your repository:
   - `shrutibabariya17-dev/PocketPilot`
4. Click **Import**.

### Step 2: Configure Project & Deploy
1. **Framework Preset**: Leave as **Other** (Vercel automatically detects `vercel.json`).
2. **Root Directory**: Leave as `./`.
3. Click the blue **Deploy** button.
4. Vercel will build and launch your application in under 30 seconds!

---

## 🗄️ Setting Up Your Free Cloud MySQL Database

Because Vercel serverless functions run in a stateless cloud environment, PocketPilot connects to any standard cloud MySQL database.

### Recommended Free MySQL Providers:
* **[TiDB Cloud](https://tidbcloud.com)**: Free forever Serverless MySQL (5 GB storage, zero maintenance).
* **[Aiven for MySQL](https://aiven.io)**: Free MySQL cloud tier.
* **[Railway](https://railway.app)**: Provision a managed MySQL database with 1 click.
* **[Clever Cloud](https://www.clever-cloud.com)**: Free shared MySQL add-on.

### Adding Database Credentials in Vercel:
1. Go to your project on the **Vercel Dashboard**.
2. Click **Settings** (top tab) &rarr; **Environment Variables** (left sidebar).
3. Add the following variables (or import a single `DATABASE_URL`):

| Variable Name | Example Value | Description |
|---|---|---|
| `DB_HOST` | `gateway01.us-east-1.prod.aws.tidbcloud.com` | Your cloud database hostname |
| `DB_PORT` | `3306` (or `4000` for TiDB) | MySQL port |
| `DB_NAME` | `pocketpilot_db` | Your database name |
| `DB_USER` | `2k8d....root` | Database username |
| `DB_PASS` | `YourSecretPassword123!` | Database password |

*(Alternative: You can provide a single `DATABASE_URL = mysql://user:pass@host:port/dbname`)*

4. Click **Save** and trigger a redeploy (or click **Deployments &rarr; Redeploy**).

### ✨ Automatic Database Table Initialization
You **do not** need to manually import `database.sql`! PocketPilot includes an **automatic self-installer** in `config/db.php`:
* On your first visit, PocketPilot detects empty tables.
* It automatically executes `database.sql`, creates the schema, inserts standard student categories, and sets up the **1-Click Student Demo Account** (`student@pocketpilot.com` / `password123`).

---

## 🛠️ Architecture on Vercel

```
[ User Browser ]
       │
       ▼
[ Vercel Edge Network ]
       ├─► /css/*, /js/*, /favicon.ico ──► Static Edge CDN (Fast, cached)
       │
       └─► /* (All pages) ──────────────► Serverless Function (api/index.php)
                                                    │
                                                    ▼
                                         [ PocketPilot Front Controller ]
                                         (Routes to index.php, dashboard.php,
                                          transactions.php, budget.php, etc.)
                                                    │
                                                    ▼
                                         [ Cloud MySQL Database ]
                                         (TiDB / Aiven / Railway)
```

### Key Vercel Optimizations Included:
1. **`vercel.json`**:
   - Maps serverless requests to `api/index.php` using `vercel-php@0.9.0`.
   - Directs CSS, JS, and image requests to Vercel's global static CDN.
2. **`api/index.php`**:
   - Universal front-controller router supporting clean URLs (`/dashboard` and `/dashboard.php`).
   - Dynamic MIME-type fallback for static assets.
3. **Stateless Session Persistence (`pp_auth`)**:
   - Signed cryptographic cookie ensures users stay logged in across serverless container cold-starts.
4. **`.vercelignore`**:
   - Excludes local build tools, `.bat` files, and `.git` artifacts to keep deployment fast and lightweight.
