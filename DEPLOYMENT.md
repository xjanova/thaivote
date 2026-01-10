# 🚀 ThaiVote Smart Deployment Guide

## Overview

**deploy.sh v5.0** เป็น deployment script ที่ฉลาดพอที่จะ:
- ✅ ตรวจสอบและติดตั้ง dependencies อัตโนมัติ
- ✅ แก้ไขปัญหาทั่วไปได้เอง (.env, database, storage, permissions)
- ✅ ไม่ต้องแก้ปัญหาเดิมซ้ำๆ
- ✅ รองรับ first-time installation และ update

---

## 🎯 Quick Start

### First Time Installation

```bash
# ติดตั้งครั้งแรก (จะติดตั้งทุกอย่างให้อัตโนมัติ)
./deploy.sh
```

หรือใช้ doctor mode สำหรับ diagnosis และ auto-fix:

```bash
./deploy.sh doctor
```

### Update/Redeploy

```bash
# Deploy อัตโนมัติ (จะติดตั้งเฉพาะสิ่งที่ขาด)
./deploy.sh
```

---

## 📋 Available Commands

### 1. **`./deploy.sh`** (Full Deployment)
ติดตั้งและตั้งค่าทุกอย่างอัตโนมัติ

**จะทำอะไร:**
- ✓ ตรวจสอบ PHP, Composer, Node.js
- ✓ ติดตั้ง dependencies (composer, npm) ถ้ายังไม่มี
- ✓ สร้าง .env จาก .env.example ถ้ายังไม่มี
- ✓ Generate APP_KEY ถ้ายังไม่มี
- ✓ สร้าง SQLite database ถ้ายังไม่มี
- ✓ Run migrations ถ้ายังไม่ได้ run
- ✓ Run seeders ถ้า database ว่าง
- ✓ สร้าง storage link ถ้ายังไม่มี
- ✓ Build frontend assets ถ้ายังไม่ได้ build
- ✓ Optimize application

**ตัวอย่าง:**
```bash
./deploy.sh
```

**Output:**
```
╔════════════════════════════════════════════════════════════╗
║  ThaiVote Smart Deployment System v5.0                    ║
║  ฉลาดพอที่จะติดตั้งและแก้ไขปัญหาทุกอย่างเอง              ║
╚════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 Checking PHP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ PHP version: 8.4.16
✓ PHP check passed
...
✅ Deployment Complete

Your application is ready!
Admin login: test@example.com / password

To start development server:
  npm run dev         # Frontend dev server
  php artisan serve  # Backend server
```

---

### 2. **`./deploy.sh doctor`** (System Doctor)
วินิจฉัยและแก้ไขปัญหาทั้งหมดอัตโนมัติ

**เมื่อไหร่ควรใช้:**
- เมื่อมีปัญหาแปลกๆ เกิดขึ้น
- เมื่อ dependencies หาย
- เมื่อ permissions ผิด
- เมื่อต้องการตรวจสุขภาพระบบ

**ตัวอย่าง:**
```bash
./deploy.sh doctor
```

**จะทำอะไร:**
- 🔍 ตรวจสอบทุกอย่างอย่างละเอียด
- 🔧 แก้ไขปัญหาอัตโนมัติ
- 📊 แสดงรายงานผล

---

### 3. **`./deploy.sh fix`** (Quick Fix)
แก้ไขปัญหาทั่วไปแบบรวดเร็ว

**เมื่อไหร่ควรใช้:**
- เมื่อ dependencies หาย
- เมื่อ assets ไม่ build
- เมื่อ permissions ผิด

**ตัวอย่าง:**
```bash
./deploy.sh fix
```

**จะทำอะไร:**
- ✓ ตรวจสอบและสร้าง .env
- ✓ ติดตั้ง dependencies ที่ขาด
- ✓ แก้ไข storage permissions
- ✓ Build assets ถ้ายังไม่มี
- ✓ Clear และ optimize caches

---

### 4. **`./deploy.sh status`** (Status Check)
แสดงสถานะของ application

**ตัวอย่าง:**
```bash
./deploy.sh status
```

**Output:**
```
✓ PHP: 8.4.16
✓ Composer: Installed
✓ Node.js: v22.21.1
✓ NPM: 10.9.4
✓ Composer dependencies: Installed
✓ NPM dependencies: Installed
✓ .env: Exists
✓ APP_KEY: Set
✓ Database: Exists
✓ Storage link: Exists
✓ Frontend assets: Built
```

---

### 5. **`./deploy.sh reset`** (Reset Installation)
Reset application ให้กลับเป็นเหมือนใหม่

**⚠️ คำเตือน:** จะลบข้อมูลทั้งหมด (database, .env, caches, assets)

**เมื่อไหร่ควรใช้:**
- เมื่อต้องการเริ่มต้นใหม่
- เมื่อมีปัญหาร้ายแรงที่แก้ไม่ได้

**ตัวอย่าง:**
```bash
./deploy.sh reset
```

**จะทำอะไร:**
- 🗑️ ลบ database (database.sqlite)
- 🗑️ ลบ .env
- 🗑️ Clear caches ทั้งหมด
- 🗑️ ลบ built assets
- ✅ **เก็บ** dependencies (vendor, node_modules)

---

## 🔧 Common Use Cases

### Case 1: Clone โปรเจคครั้งแรก

```bash
git clone <repository-url>
cd thaivote
./deploy.sh
```

**Result:** ระบบจะติดตั้งทุกอย่างให้อัตโนมัติ

---

### Case 2: Dependencies หาย

**ปัญหา:**
- ไม่มี vendor/
- ไม่มี node_modules/
- ไอคอนไม่แสดง

**วิธีแก้:**
```bash
./deploy.sh doctor
```

หรือ

```bash
./deploy.sh fix
```

---

### Case 3: Settings form บันทึกไม่ได้

**ปัญหา:**
- ปุ่มบันทึกไม่ทำงาน
- Storage permissions ผิด

**วิธีแก้:**
```bash
./deploy.sh doctor
```

---

### Case 4: Assets ไม่ build

**ปัญหา:**
- CSS, JavaScript ไม่โหลด
- หน้าเว็บไม่แสดงผล

**วิธีแก้:**
```bash
./deploy.sh fix
```

หรือ manual:

```bash
npm install
npm run build
```

---

### Case 5: Database หาย

**ปัญหา:**
- ไม่มี database.sqlite
- Migrations ยังไม่ได้ run

**วิธีแก้:**
```bash
./deploy.sh doctor
```

---

### Case 6: .env หาย

**ปัญหา:**
- ไม่มี .env file
- APP_KEY ไม่มี

**วิธีแก้:**
```bash
./deploy.sh doctor
```

---

### Case 7: เริ่มต้นใหม่ทั้งหมด

**วิธีทำ:**
```bash
./deploy.sh reset
./deploy.sh
```

---

## 📝 Logs

Script จะสร้าง logs อัตโนมัติ:

```
storage/logs/deploy/deploy_YYYYMMDD_HHMMSS.log
```

**ดู logs:**
```bash
tail -f storage/logs/deploy/deploy_*.log
```

---

## 🐛 Troubleshooting

### ปัญหา: Script ไม่ run

**วิธีแก้:**
```bash
chmod +x deploy.sh
```

---

### ปัญหา: Permission denied

**วิธีแก้:**
```bash
# ให้สิทธิ์ storage และ bootstrap
chmod -R 775 storage bootstrap/cache
```

หรือใช้ doctor:

```bash
./deploy.sh doctor
```

---

### ปัญหา: Composer install ล้มเหลว

**วิธีแก้:**
```bash
# ลบ composer.lock แล้วลองใหม่
rm composer.lock
./deploy.sh
```

---

### ปัญหา: NPM install ล้มเหลว

**วิธีแก้:**
```bash
# ลบ node_modules แล้วลองใหม่
rm -rf node_modules
npm install
```

---

### ปัญหา: Database connection failed

**วิธีแก้:**

1. ตรวจสอบ .env:
```bash
DB_CONNECTION=sqlite
```

2. สร้าง database:
```bash
touch database/database.sqlite
./deploy.sh doctor
```

---

## 🔐 Default Credentials

**Admin User:**
- Email: `admin@thaivote.com`
- Password: `password`

**Test User (is_admin = true):**
- Email: `test@example.com`
- Password: `password`

---

## 🎓 Best Practices

### 1. ใช้ `doctor` เมื่อมีปัญหา
```bash
./deploy.sh doctor
```

### 2. ตรวจสอบ `status` ก่อน deploy
```bash
./deploy.sh status
./deploy.sh
```

### 3. ใช้ `fix` สำหรับ quick fixes
```bash
./deploy.sh fix
```

### 4. Commit dependencies
- ✅ **DO** commit: `package-lock.json`, `composer.lock`
- ❌ **DON'T** commit: `node_modules/`, `vendor/`, `.env`

### 5. Use `.gitignore` correctly
```gitignore
/node_modules
/vendor
.env
/public_html/build
```

---

## 🚦 Development Workflow

### Daily Development

```bash
# 1. Pull latest changes
git pull

# 2. Check status
./deploy.sh status

# 3. Fix if needed
./deploy.sh fix

# 4. Start dev servers
npm run dev         # Terminal 1
php artisan serve   # Terminal 2
```

---

### Deploying to Production

```bash
# 1. Set environment to production
echo "APP_ENV=production" >> .env

# 2. Deploy
./deploy.sh

# 3. Verify
./deploy.sh status
```

---

## 📚 Additional Resources

- **Project Documentation**: `/docs`
- **Code Style Guide**: `CLAUDE.md`
- **API Documentation**: Visit `/api-docs` when server is running
- **Laravel Documentation**: https://laravel.com/docs
- **Vue.js Documentation**: https://vuejs.org/guide

---

## 🆘 Need Help?

1. **Check Status First:**
   ```bash
   ./deploy.sh status
   ```

2. **Run Doctor:**
   ```bash
   ./deploy.sh doctor
   ```

3. **Check Logs:**
   ```bash
   tail -f storage/logs/deploy/deploy_*.log
   ```

4. **Ask for Help:**
   - Create an issue in the repository
   - Include output from `./deploy.sh status`
   - Include relevant log files

---

**Happy Deploying! 🚀**
