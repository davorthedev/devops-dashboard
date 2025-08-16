<h1 align="center">🔧📊 DevOps Dashboard — <em>Multi‑Language Edition</em></h1>

<p align="center">
  <a href="#"><img alt="PHP" src="https://img.shields.io/badge/PHP-8.1+-777bb4?logo=php&logoColor=white"></a>
  <a href="#"><img alt="Symfony" src="https://img.shields.io/badge/Symfony-6.4-black?logo=symfony"></a>
  <a href="#"><img alt="React" src="https://img.shields.io/badge/React-18-61dafb?logo=react&logoColor=222"></a>
  <a href="#"><img alt="PostgreSQL" src="https://img.shields.io/badge/PostgreSQL-15-336791?logo=postgresql&logoColor=white"></a>
  <a href="#"><img alt="MySQL" src="https://img.shields.io/badge/MySQL-8.0-00758f?logo=mysql&logoColor=white"></a>
  <a href="#"><img alt="License" src="https://img.shields.io/badge/License-MIT-9cf"></a>
</p>

<p align="center">A cross‑language DevOps dashboard: <strong>Symfony (PHP)</strong> API, low‑level collectors in <strong>C/Python/Ruby</strong>, persistence with <strong>Doctrine</strong>, and a <strong>React</strong> UI with live charts.</p>

---

## 📚 Table of Contents

* [Highlights](#-highlights)
* [Architecture](#-architecture)
* [Repository Layout](#-repository-layout)
* [Tech Stack](#-tech-stack)
* [Prerequisites](#-prerequisites)
* [Quick Start](#-quick-start)

    * [1) Clone](#1-clone)
    * [2) Build the C Collector](#2-build-the-c-collector)
    * [3) Backend (Symfony)](#3-backend-symfony)
    * [4) Frontend (React)](#4-frontend-react)
    * [5) Cron (Every 5 min)](#5-cron-every-5-min))
* [API](#-api)
* [Screenshots](#-screenshots)
* [License](#-license)

---

## ✨ Highlights

* ⚡️ **Low‑level metrics** via C binary (reads `/proc`) → fast and accurate
* 🕵️ **Anomaly scan** via Python (`/var/log/auth.log`): failed SSH logins + suspicious IPs
* 🧰 **Service control** via Ruby wrapper around `systemctl`
* 🧱 **Symfony 6.4** REST API + Doctrine ORM (PostgreSQL/MySQL)
* 📈 **React + Chart.js** for live charts (24h/7d)
* ⏱️ **Cron** to persist metrics periodically

> Project root (example): `/home/ponchek/projects/devops-dashboard`

---

## 🗺️ Architecture
➡️ **Standalone diagram:** [docs/architecture.mmd](docs/architecture.mmd)

---

## 🗂️ Repository Layout

```
devops-dashboard/
├── backend/                 # Symfony 6.4 API
│   ├── src/                 # Controllers, Services, Entity, Command, Repository
│   ├── config/              # services.yaml etc.
│   ├── bin/console          # Symfony console
│   └── composer.json
├── scripts/                 # Cross‑language utilities
│   ├── sys_stats.c          # C source (→ ./sys_stats)
│   ├── log_analyzer.py      # Python3 log parser
│   └── service_manager.rb   # Ruby systemctl wrapper
├── frontend/                # React (CRA)
│   └── src/components/      # MetricsChart, LogsView, ServiceControls
└── docs/
    ├── architecture.mmd     # Mermaid source
    └── screenshots/
        ├── dashboard_overview.png
```

---

## 🧰 Tech Stack

* **Backend**: PHP ≥8.1, Symfony 6.4, Doctrine ORM, Symfony Process, Nelmio CORS
* **DB**: PostgreSQL **or** MySQL/MariaDB
* **Collectors**: C (GCC), Python 3, Ruby (systemctl)
* **Frontend**: React 18, Chart.js via `react-chartjs-2`
* **OS**: Linux (Ubuntu/Debian) or WSL2 Ubuntu on Windows 10

---

## ✅ Prerequisites

Install core tools (Ubuntu/WSL2 example):

```bash
sudo apt update && sudo apt install -y \
  build-essential python3 python3-venv ruby ruby-dev \
  pkg-config libpq-dev default-mysql-client \
  git curl
```

Also install **PHP ≥ 8.1**, **Composer**, **Symfony CLI**, **Node.js ≥ 18** & **npm**.

---

## 🚀 Quick Start

### 1) Clone

```bash
cd /home/ponchek/projects
git clone <YOUR_REPO_URL> devops-dashboard
cd devops-dashboard
```

### 2) Build the C Collector

```bash
cd scripts
gcc sys_stats.c -o sys_stats
chmod +x sys_stats log_analyzer.py service_manager.rb
cd ..
```

> Backend expects the binary at `scripts/sys_stats` (see `backend/config/services.yaml`).

### 3) Backend (Symfony)

```bash
cd backend
composer install

# Configure DB
cp .env .env.local# MySQL example
# DATABASE_URL="mysql://user:pass@127.0.0.1:3306/devops_dashboard?serverVersion=8.0&charset=utf8mb4"

# Create DB + schema
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n

# Run server (dev)
symfony server:start -d   # or: php -S 127.0.0.1:8000 -t public
```

### 4) Frontend (React)

```bash
cd ../frontend
npm install

echo "REACT_APP_API_URL=http://127.0.0.1:8000" > .env

npm start            
```

### 5) Cron (Every 5 min)

**Use absolute paths.** Example:

```bash
crontab -e
*/5 * * * * /usr/bin/php /home/ponchek/projects/devops-dashboard/backend/bin/console app:store-metrics --env=prod >> /var/log/devops-dashboard-cron.log 2>&1
```


---

## 🔌 API

Base URL (default dev): `http://127.0.0.1:8000`

### Metrics

* `GET /api/metrics` → on‑demand snapshot (also persists)

```json
{"cpu":14.21,"memory":37.55,"disk":62.10}
```

* `GET /api/metrics/history[?from=YYYY-MM-DD HH:MM:SS]` → timeline since `from` (default: last 24h)

```json
[{"recordedAt":"2025-08-15 12:00:00","cpu":10.1,"memory":35.2,"disk":62.1}]
```

### Logs / Anomalies

* `GET /api/logs` → last 24h failed SSH logins + unique IPs

```json
{"failed_logins":7,"suspicious_ips":["203.0.113.42","198.51.100.101"]}
```

> Default log on Debian/Ubuntu: `/var/log/auth.log`. On RHEL/CentOS use `/var/log/secure` (pass as arg or change default).

### Services

* `GET /api/services/status?names=mysql,nginx,postgres` → array of statuses
* `GET /api/services/{action}/{service}` → `start|stop|restart|status`

```json
[{"name":"nginx","status":"active","success":true}]
```



---

## 🖼️ Screenshots

![Dashboard Overview](docs/screenshots/dashboard_overview.png)

---

---

## 📄 License

This project is licensed under the **MIT License**. See [LICENSE](LICENSE) for details.
