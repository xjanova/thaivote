# 🚀 ThaiVote Deployment Guide

## Overview

**deploy.sh v4.1** - Smart Automated Deployment Script

### ✨ Features
- ✅ **บังคับติดตั้ง dependencies ทุกครั้ง** (Composer + NPM)
- ✅ **Pull latest code** จาก git อัตโนมัติ
- ✅ **Smart migration** handling with error detection
- ✅ **Intelligent seeding** (skip existing data)
- ✅ **Auto-repair** system issues
- ✅ **Rollback on failure** อัตโนมัติ
- ✅ **Detailed logging** และ error reports
- ✅ **Force reset** option สำหรับการติดตั้งใหม่ทั้งหมด

---

## 🎯 Quick Start

### First Time Installation

```bash
# ติดตั้งครั้งแรก
./deploy.sh

# หรือใช้ doctor mode (ตรวจสอบและแก้ไขอัตโนมัติ)
./deploy.sh doctor
```

### Update/Redeploy

```bash
# Production deployment (มี git pull + ติดตั้ง dependencies ทุกครั้ง)
./deploy.sh

# Quick deployment (ไม่มี backup)
./deploy.sh quick
```

---

## 📋 Available Commands

### 1. **`./deploy.sh`** หรือ **`./deploy.sh deploy`**
**Full deployment** - ติดตั้งและตั้งค่าทุกอย่าง

**จะทำอะไร (ตามลำดับ):**
1. ✓ **Preflight checks** - ตรวจสอบ disk space, PHP version
2. ✓ **Bootstrap Laravel** - สร้าง directories ที่จำเป็น (storage, cache, etc.)
3. ✓ **Check environment** - ตรวจสอบ .env และ database
4. ✓ **Backup database** (ถ้าเปิด --backup)
5. ✓ **Setup database** - สร้าง SQLite database ถ้ายังไม่มี
6. ✓ **Enable maintenance mode** - ป้องกัน user เข้าถึงระหว่าง deploy
7. ✓ **🔥 Pull latest code** - git fetch + git pull (auto-stash uncommitted changes)
8. ✓ **🔥 Install Composer dependencies** - บังคับติดตั้งทุกครั้ง (95 packages)
9. ✓ **🔥 Install NPM dependencies** - บังคับติดตั้งทุกครั้ง (431 packages)
10. ✓ **🔥 Build frontend assets** - npm run build ทุกครั้ง
11. ✓ **Run migrations** - Smart migration with error detection
12. ✓ **Clear caches** - Config, route, view caches
13. ✓ **Optimize application** - Cache config, routes (production only)
14. ✓ **Setup storage links** - Symlink storage/app/public → public_html/storage
15. ✓ **Fix permissions** - chmod storage, bootstrap/cache
16. ✓ **Run seeders** - ถ้ามี --seed flag หรือ database ว่าง
17. ✓ **Create admin user** - ถ้ามี --admin flag
18. ✓ **Health check** - ทดสอบว่าระบบทำงานได้
19. ✓ **Disable maintenance mode** - เปิดให้ใช้งานได้อีกครั้ง

**ตัวอย่าง:**
```bash
./deploy.sh                    # Full deployment
./deploy.sh deploy             # เหมือนกัน
./deploy.sh deploy --seed      # พร้อม run seeders
./deploy.sh deploy --admin     # พร้อมสร้าง admin user
./deploy.sh --backup           # เปิด database backup
```

**Output:**
```
╔══════════════════════════════════════════════════════════════════════════╗
║                                                                          ║
║                        🚀 ThaiVote Deployment                           ║
║                     Smart Automated Deployment v4.1                     ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 STEP 5: Pulling Latest Code
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ 📍 Current branch: main
ℹ 📦 Current commit: abc1234 - Fix backend settings
[✓] Fetched updates from remote ✓
ℹ Pulling latest changes...
[✓] Successfully pulled latest code ✓

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 STEP 6: Installing Composer Dependencies
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ 📦 Installing/Updating ALL Composer dependencies...
[✓] Composer dependencies installed ✓

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 STEP 7: Installing NPM Dependencies
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ 📦 Installing/Updating ALL NPM dependencies...
[✓] NPM dependencies installed ✓

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 STEP 8: Building Frontend Assets
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ 🏗️  Building ALL frontend assets with Vite...
[✓] Frontend assets built ✓

...

╔════════════════════════════════════════════════════════════════════════╗
║                    ✅ DEPLOYMENT SUCCESSFUL! ✅                        ║
║                                                                        ║
║  🎉 ThaiVote has been successfully deployed!                          ║
║  ⏱️  Deployment took 2 minutes 34 seconds                             ║
╚════════════════════════════════════════════════════════════════════════╝
```

---

### 2. **`./deploy.sh quick`**
**Quick deployment** - เหมือน deploy แต่ไม่มี backup

**ความแตกต่าง:**
- ❌ ไม่ backup database (เร็วกว่า)
- ✅ ทุกอย่างอื่นเหมือนกัน

**ตัวอย่าง:**
```bash
./deploy.sh quick
```

---

### 3. **`./deploy.sh repair`**
**Auto-repair** - ซ่อมแซมปัญหาอัตโนมัติ

**เมื่อไหร่ควรใช้:**
- Dependencies หาย (vendor/, node_modules/)
- Permissions ผิด (storage/, bootstrap/cache/)
- Cache เก่า (config.php, routes-v7.php)
- Storage link หาย
- .env ผิดพลาด

**จะทำอะไร:**
1. ✓ สร้าง directories ที่จำเป็นทั้งหมด
2. ✓ ติดตั้ง Composer dependencies ใหม่
3. ✓ ติดตั้ง NPM dependencies ใหม่
4. ✓ Build frontend assets ใหม่
5. ✓ Fix permissions (chmod 775)
6. ✓ Clear all caches
7. ✓ Setup storage links
8. ✓ Generate APP_KEY (ถ้ายังไม่มี)

**ตัวอย่าง:**
```bash
./deploy.sh repair
```

---

### 4. **`./deploy.sh diagnose`**
**Diagnose** - ตรวจสอบปัญหา (ไม่แก้ไข)

**จะทำอะไร:**
- 🔍 ตรวจสอบ PHP extensions
- 🔍 ตรวจสอบ file permissions
- 🔍 ตรวจสอบ .env configuration
- 🔍 ตรวจสอบ database connection
- 🔍 ตรวจสอบ storage links
- 🔍 ตรวจสอบ dependencies
- 📊 แสดงรายงานปัญหาทั้งหมด

**ตัวอย่าง:**
```bash
./deploy.sh diagnose
```

---

### 5. **`./deploy.sh doctor`**
**Doctor** - ตรวจสอบ + แก้ไขอัตโนมัติ

**= `diagnose` + `repair`**

**ตัวอย่าง:**
```bash
./deploy.sh doctor
```

---

### 6. **`./deploy.sh force-reset`**
**⚠️  Nuclear option** - ลบและติดตั้งใหม่ทั้งหมด (ไม่ลบ database)

**จะลบ:**
- ❌ vendor/
- ❌ node_modules/
- ❌ public_html/build/
- ❌ bootstrap/cache/*
- ❌ storage/framework/cache/*
- ❌ storage/framework/views/*

**จะเก็บ:**
- ✅ database/database.sqlite
- ✅ .env
- ✅ storage/logs/

**แล้วติดตั้งใหม่ทั้งหมด**

**เมื่อไหร่ควรใช้:**
- เมื่อระบบพังหนักมาก
- เมื่อ dependencies conflict
- เมื่อต้องการเริ่มต้นใหม่

**ตัวอย่าง:**
```bash
./deploy.sh force-reset
# จะถามยืนยันก่อน (yes/no)
```

---

### 7. **`./deploy.sh status`**
**Status** - แสดงสถานะของ application

**จะแสดง:**
- ✓ PHP version
- ✓ Composer version
- ✓ Node.js + NPM version
- ✓ Composer dependencies status
- ✓ NPM dependencies status
- ✓ .env status
- ✓ APP_KEY status
- ✓ Database status
- ✓ Storage link status
- ✓ Frontend assets status
- ✓ Disk space usage
- ✓ Recent deployments

**ตัวอย่าง:**
```bash
./deploy.sh status
```

---

## 🚩 Available Options (Flags)

### `--seed`
**บังคับ run database seeders**

```bash
./deploy.sh --seed
./deploy.sh deploy --seed
```

### `--admin`
**สร้าง admin user**

```bash
./deploy.sh --admin
./deploy.sh deploy --admin

# จะสร้าง:
# Email: admin@thaivote.com
# Password: (จะให้ input)
```

### `--backup`
**เปิด database backup**

```bash
./deploy.sh --backup
# จะสร้าง backup ใน storage/backups/
```

### `--fresh-composer`
**Force regenerate composer.lock**

```bash
./deploy.sh --fresh-composer
```

### `--skip-npm`
**ข้าม NPM install และ build**

```bash
./deploy.sh --skip-npm
```

### `--verbose` หรือ `-v`
**แสดง verbose output**

```bash
./deploy.sh --verbose
./deploy.sh -v
```

---

## 🔥 Key Improvements ใน v4.1

### **1. บังคับติดตั้ง Dependencies ทุกครั้ง**

**เดิม (ก่อน v4.1):**
```bash
# Check ว่ามี vendor/ ไหม
if [ -d "vendor" ]; then
    echo "✓ Skip" # ไม่ติดตั้ง
fi
```

**ใหม่ (v4.1):**
```bash
# ติดตั้งทุกครั้ง ไม่ skip
log_info "📦 Installing/Updating ALL Composer dependencies..."
composer install # ทุกครั้ง!
```

**ผลลัพธ์:**
- ✅ ไม่มีปัญหา dependencies หาย
- ✅ Dependencies เป็น version ล่าสุดเสมอ
- ✅ ไม่ต้องแก้ปัญหาซ้ำๆ

---

## 📊 Deployment Flow

```
1.  Preflight Checks        → disk space, PHP version
2.  Bootstrap Laravel        → create directories
3.  Check Environment        → .env, database config
4.  Backup Database          → (if --backup)
5.  🔥 Pull Latest Code     → git pull (auto-stash)
6.  🔥 Install Composer     → บังคับติดตั้งทุกครั้ง
7.  🔥 Install NPM          → บังคับติดตั้งทุกครั้ง
8.  🔥 Build Frontend       → บังคับ build ทุกครั้ง
9.  Run Migrations          → smart migration
10. Clear Caches            → config, route, view
11. Optimize Application    → cache (production only)
12. Setup Storage Links     → symlink
13. Fix Permissions         → chmod 775
14. Restart Services        → queue, reverb (if running)
15. Run Seeders            → (if --seed or empty DB)
16. Create Admin User      → (if --admin)
17. Health Check           → test database, routes
18. Disable Maintenance    → ปิด maintenance mode
```

---

## 🔧 Common Use Cases

### Case 1: Clone โปรเจคครั้งแรก

```bash
git clone <repository>
cd thaivote
./deploy.sh

# จะติดตั้งทุกอย่างให้อัตโนมัติ:
# - vendor/ (95 packages)
# - node_modules/ (431 packages)
# - public_html/build/
# - database, migrations, seeders
```

---

### Case 2: Update โค้ดใหม่

```bash
# อยู่ใน project directory แล้ว
./deploy.sh

# จะทำอัตโนมัติ:
# 1. git pull (ดึงโค้ดใหม่)
# 2. composer install (อัปเดต dependencies)
# 3. npm install (อัปเดต dependencies)
# 4. npm run build (build assets ใหม่)
# 5. php artisan migrate (run migrations ใหม่)
```

---

### Case 3: Dependencies หาย

**ปัญหา:**
- ไม่มี vendor/
- ไม่มี node_modules/
- ไอคอนไม่แสดง
- Tailwind CSS ไม่ทำงาน

**วิธีแก้:**
```bash
./deploy.sh repair

# หรือ
./deploy.sh doctor
```

---

### Case 4: ระบบพังหนัก

**ปัญหา:**
- Dependencies conflict
- Cache เก่าเยอะ
- Permissions ยุ่ง

**วิธีแก้:**
```bash
./deploy.sh force-reset
# ลบและติดตั้งใหม่ทั้งหมด (ไม่ลบ database)
```

---

### Case 5: Production Deployment

```bash
# เซิร์ฟเวอร์ production
cd /var/www/thaivote
./deploy.sh --backup

# จะทำอัตโนมัติ:
# 1. Backup database
# 2. git pull
# 3. install dependencies
# 4. run migrations
# 5. build assets
# 6. optimize (cache config, routes)
```

---

## 📝 Logs

Script จะสร้าง logs อัตโนมัติ:

```
storage/logs/deploy/
├── deploy_20260110_143052.log    # ทุกขั้นตอน
└── error_20260110_143052.log     # เฉพาะ errors
```

**ดู logs:**
```bash
# Log ล่าสุด
tail -f storage/logs/deploy/deploy_*.log

# Errors
tail -f storage/logs/deploy/error_*.log

# ทุกไฟล์
ls -lh storage/logs/deploy/
```

---

## 🐛 Troubleshooting

### ปัญหา: Script ไม่ run

```bash
chmod +x deploy.sh
```

---

### ปัญหา: Permission denied

```bash
# ให้สิทธิ์ storage และ bootstrap
chmod -R 775 storage bootstrap/cache

# หรือใช้ repair
./deploy.sh repair
```

---

### ปัญหา: Composer install ล้มเหลว

```bash
# ลบ composer.lock แล้วลองใหม่
rm composer.lock
./deploy.sh --fresh-composer
```

---

### ปัญหา: NPM install ล้มเหลว

```bash
# ลบ node_modules แล้วลองใหม่
rm -rf node_modules package-lock.json
npm install
```

---

### ปัญหา: Git pull conflict

```bash
# Script จะ auto-stash ให้
# แต่ถ้ายังมีปัญหา:
git stash
./deploy.sh
git stash pop
```

---

## 🔐 Default Credentials

**Admin User (ถ้าใช้ --admin):**
- Email: `admin@thaivote.com`
- Password: (ตั้งเองตอน deploy)

**Test User (from seeders):**
- Email: `test@example.com`
- Password: `password`
- is_admin: `true`

---

## ⚡ Performance

### Full Deployment
```
Total time: 2-5 minutes

Breakdown:
- Git pull:         5-10 seconds
- Composer install: 30-60 seconds
- NPM install:      30-60 seconds
- Build assets:     10-30 seconds
- Migrations:       5-10 seconds
- Seeders:          5-10 seconds
- Optimization:     5-10 seconds
```

### Quick Deployment
```
Total time: 30-60 seconds

Breakdown:
- Composer install: 10-20 seconds (cached)
- NPM install:      10-20 seconds (cached)
- Build assets:     10-30 seconds
```

---

## 📚 Additional Resources

- **Project Documentation**: `CLAUDE.md`
- **Testing Guide**: `TESTING_DEPLOYMENT.md`
- **API Documentation**: Visit `/api-docs`
- **Laravel Documentation**: https://laravel.com/docs
- **Vue.js Documentation**: https://vuejs.org/guide

---

## 🆘 Need Help?

1. **Check Status:**
   ```bash
   ./deploy.sh status
   ```

2. **Run Doctor:**
   ```bash
   ./deploy.sh doctor
   ```

3. **Check Logs:**
   ```bash
   tail -f storage/logs/deploy/*.log
   ```

4. **Force Reset:**
   ```bash
   ./deploy.sh force-reset
   ```

---

**Happy Deploying! 🚀**

*ThaiVote Deploy Script v4.1*
