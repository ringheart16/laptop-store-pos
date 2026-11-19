# Laptop Store Management & POS System

A clean, aesthetic, and high-performance **Laptop Store Management & POS System** crafted with **Native PHP**, **Tailwind CSS**, and **Node.js**.

Designed for both **desktop monitors** and **mobile phones / tablets**, and fully configured for zero-friction deployment to **Render.com** (with Docker & automated database seeding).

---

## ✨ Mobile & Touch-First Enhancements

- 📱 **Mobile Slide-Over Drawer**: Responsive off-canvas navigation with backdrop blur and touch dismiss.
- ⚡ **Mobile Bottom Navigation Bar**: Quick native-like staff bottom bar for instant access to **Home**, **POS**, **Laptops Catalog**, **Sales Log**, and **Menu**.
- 🛒 **Mobile POS Dual Mode**: Switch effortlessly between the **Laptops Catalog** and **Active Cart** with live badge counters.
- 💳 **Sticky Mobile Checkout Bar**: Floating `View Cart & Total` pill for quick thumb checkouts.
- 📊 **Responsive Data Tables**: Horizontal scrolling wrappers (`overflow-x-auto`) and compact touch targets on mobile viewports.
- 🔍 **iOS Auto-Zoom Prevention**: Inputs styled with 16px font-size to prevent automatic Safari zooming on mobile devices.
- 🌓 **Instant Dark & Light Mode**: Smooth theme toggling with anti-FOUC initialization and `localStorage` persistence.

---

## 🚀 How to Deploy on Render.com

Render supports PHP applications via **Docker**. This project comes with a production-ready `Dockerfile`, `docker-entrypoint.sh`, and `render.yaml` Blueprint.

### Option 1: Fast Deploy via Render Dashboard (Recommended)

1. **Push your code to GitHub / GitLab**.
2. Go to your [Render Dashboard](https://dashboard.render.com/) and click **New +** &rarr; **Web Service**.
3. Select your repository.
4. Render will automatically detect the **Dockerfile**:
   - **Name**: `laptop-store-pos` (or your preferred name)
   - **Runtime**: `Docker`
   - **Instance Type**: `Free`
5. *(Optional)* **Connect to a Cloud MySQL Database**:
   Add the following Environment Variables in the Render settings:
   - `DB_HOST`: Your MySQL host (e.g. from Aiven, TiDB Serverless, Clever Cloud, or Railway)
   - `DB_PORT`: `3306`
   - `DB_NAME`: `laptop_store_db` (or your remote database name)
   - `DB_USER`: Your MySQL username
   - `DB_PASS`: Your MySQL password
   *(Or simply set `DATABASE_URL=mysql://user:pass@host:3306/dbname`)*

> [!NOTE]
> **Zero-Config Cloud Fallback**: If you deploy without configuring a remote MySQL database, the application automatically initializes an embedded **SQLite** database seeded with laptops, customers, and staff accounts. It will NEVER crash or show a blank screen!

---

### Option 2: Deploy via Render Blueprint (`render.yaml`)

1. Connect your repository in Render under **Blueprints**.
2. Render reads [render.yaml](render.yaml) and provisions the service automatically.
3. Click **Apply** to deploy!

---

## 💻 Running Locally on Windows (XAMPP)

1. Open XAMPP and start **Apache** and **MySQL**.
2. Ensure your database is named `laptop_store_db`.
3. Open your browser and navigate to:
   ```
   http://localhost/laptop/
   ```

### Running via Node.js Dev Server
```powershell
cd C:\xampp\htdocs\laptop
npm run dev
```
*(Or double-click `run.bat` to launch dev server at `http://localhost:8000`)*

---

## 🔑 Default Staff Credentials

| Role | Username | Password | Notes |
|---|---|---|---|
| **Admin** | `admin` | `password` | Full system access & settings |
| **Manager** | `manager` | `password` | Inventory, reports & sales |
| **Cashier** | `maria` | `12345` | POS checkout & customer lookups |

*New staff accounts can also be created on the fly via the **Create Account** button at `index.php?page=register`.*

---

## 📂 Streamlined Architecture

- `Dockerfile` & `docker-entrypoint.sh` — Render dynamic port binding and Apache container configuration
- `render.yaml` — Render.com Blueprint specification
- `index.php` — Clean front controller & router
- `api/config/database.php` — Cloud-ready PDO connector (MySQL with SQLite cloud fallback)
- `api/sales/create.php` — Transactional POS checkout & serial number generator
- `includes/` — Header, desktop sidebar, mobile drawer, topbar, footer (with mobile bottom bar), helpers
- `pages/` — Dashboard, POS, Products, Inventory, Sales, Customers, Suppliers, Reports, Users, Settings, Login, Register
- `assets/` — Compiled Tailwind CSS (`style.css`), app JavaScript (`app.js`), Lucide icons, Chart.js
