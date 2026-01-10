# 🧪 Testing Deploy.sh - ทดสอบว่าติดตั้งจริง

## ปัญหาเดิม ❌

```bash
# Clone โปรเจคใหม่
git clone <repo>
cd thaivote

# รัน deploy.sh
./deploy.sh

# ผลลัพธ์: ไม่เห็นติดตั้งอะไรเลย!
# สาเหตุ: deploy.sh แค่ "check" แต่ไม่ได้ "install" จริง
```

## แก้ไขแล้ว ✅

```bash
# Clone โปรเจคใหม่
git clone <repo>
cd thaivote

# รัน deploy.sh
./deploy.sh

# ผลลัพธ์: ติดตั้งทุกอย่างอัตโนมัติ!
# - สร้าง directories (storage, database, build)
# - ติดตั้ง Composer (95 packages)
# - ติดตั้ง NPM (431 packages)
# - สร้าง .env
# - Generate APP_KEY
# - สร้าง database
# - Run migrations
# - Run seeders
# - Build assets
```

---

## 📋 วิธีทดสอบ

### ทดสอบ 1: Fresh Installation

```bash
# 1. Clone โปรเจค (หรือลบ dependencies)
cd /tmp
rm -rf thaivote-test
git clone <your-repo> thaivote-test
cd thaivote-test

# 2. ตรวจสอบว่าไม่มี dependencies
ls vendor/      # ควรไม่มี
ls node_modules/ # ควรไม่มี

# 3. รัน deploy.sh
./deploy.sh

# 4. ตรวจสอบว่าติดตั้งแล้ว
ls vendor/      # ควรมี 95 packages
ls node_modules/ # ควรมี 431 packages
ls public_html/build/ # ควรมี assets

# 5. ตรวจสอบสถานะ
./deploy.sh status
```

**Expected Output:**
```
╔════════════════════════════════════════════════════════════╗
║  ThaiVote Smart Deployment System v5.0                    ║
║  ฉลาดพอที่จะติดตั้งและแก้ไขปัญหาทุกอย่างเอง              ║
╚════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 Creating Required Directories
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ Creating storage/app/public...
ℹ Creating storage/framework/cache/data...
ℹ Creating storage/framework/sessions...
ℹ Creating storage/framework/views...
✓ Required directories created

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 Checking & Installing Dependencies
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠ Composer dependencies not installed
ℹ Installing Composer dependencies...
Installing dependencies from lock file
[กำลังติดตั้ง 95 packages...]
✓ Composer dependencies installed

⚠ NPM dependencies not installed
ℹ Installing NPM dependencies (this may take a few minutes)...
[กำลังติดตั้ง 431 packages...]
✓ NPM dependencies installed

... [ขั้นตอนอื่นๆ]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📌 ✅ Deployment Complete
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Your application is ready!
Admin login: test@example.com / password
```

---

### ทดสอบ 2: ลบ Dependencies แล้วติดตั้งใหม่

```bash
# 1. ไปที่โปรเจค
cd /path/to/thaivote

# 2. ลบ dependencies
rm -rf vendor node_modules public_html/build

# 3. ตรวจสอบสถานะ (ควรเห็น ✗)
./deploy.sh status

# Expected:
# ✗ Composer dependencies: Not installed
# ✗ NPM dependencies: Not installed
# ✗ Frontend assets: Not built

# 4. รัน deploy.sh
./deploy.sh

# ควรเห็นการติดตั้งจริง:
# ✓ Installing Composer dependencies...
# ✓ Installing NPM dependencies...
# ✓ Building frontend assets...

# 5. ตรวจสอบอีกครั้ง (ควรเห็น ✓ ทั้งหมด)
./deploy.sh status

# Expected:
# ✓ Composer dependencies: Installed
# ✓ NPM dependencies: Installed
# ✓ Frontend assets: Built
```

---

### ทดสอบ 3: Doctor Mode

```bash
# 1. ลบบางส่วน
rm -rf vendor
rm .env
rm database/database.sqlite

# 2. รัน doctor
./deploy.sh doctor

# ควรแก้ไขปัญหาทั้งหมดอัตโนมัติ:
# ✓ Installing Composer dependencies...
# ✓ Creating .env from .env.example...
# ✓ Generating APP_KEY...
# ✓ Creating SQLite database...

# 3. ตรวจสอบว่าทุกอย่างพร้อม
./deploy.sh status
```

---

### ทดสอบ 4: Quick Fix

```bash
# 1. ลบ assets
rm -rf public_html/build/*

# 2. รัน fix
./deploy.sh fix

# ควรเห็น:
# ✓ Building frontend assets...

# 3. ตรวจสอบ
ls public_html/build/
# ควรมี: manifest.json, app-*.css, app-*.js
```

---

## ✅ Checklist การทดสอบ

### Pre-Deployment
- [ ] PHP 8.2+ installed
- [ ] Composer installed
- [ ] Node.js 18+ installed
- [ ] NPM installed

### During Deployment
- [ ] **เห็นการติดตั้ง Composer dependencies จริง** (ไม่ใช่แค่ skip)
- [ ] **เห็นการติดตั้ง NPM dependencies จริง**
- [ ] เห็นการสร้าง directories (storage/*, database/, etc.)
- [ ] เห็นการสร้าง .env (ถ้ายังไม่มี)
- [ ] เห็นการ generate APP_KEY (ถ้ายังไม่มี)
- [ ] เห็นการสร้าง database (ถ้ายังไม่มี)
- [ ] เห็นการ run migrations
- [ ] เห็นการ run seeders
- [ ] เห็นการ build assets (npm run build)

### Post-Deployment
- [ ] `./deploy.sh status` แสดง ✓ ทั้งหมด
- [ ] `ls vendor/` มี packages (95 packages)
- [ ] `ls node_modules/` มี packages (431 packages)
- [ ] `ls public_html/build/` มี assets
- [ ] `.env` มี APP_KEY
- [ ] `database/database.sqlite` มีไฟล์
- [ ] `php artisan migrate:status` แสดง migrations ที่ run แล้ว
- [ ] สามารถ login ด้วย test@example.com / password
- [ ] Admin backend ใช้งานได้ (`/admin`)
- [ ] Settings form บันทึกได้

---

## 🐛 Common Issues

### Issue 1: "Composer not found"

**Solution:**
```bash
# deploy.sh จะดาวน์โหลด composer.phar ให้อัตโนมัติ
# หรือติดตั้งเอง:
curl -sS https://getcomposer.org/installer | php
```

---

### Issue 2: "npm install fails"

**Solution:**
```bash
# ลบ node_modules และ package-lock.json
rm -rf node_modules package-lock.json

# รันใหม่
npm install
```

---

### Issue 3: "Permission denied"

**Solution:**
```bash
# ให้สิทธิ์ execute
chmod +x deploy.sh

# ให้สิทธิ์ storage และ bootstrap
chmod -R 775 storage bootstrap/cache
```

---

### Issue 4: "ไม่เห็นการติดตั้ง"

**สาเหตุ:** Dependencies มีอยู่แล้ว ดังนั้น script จะ skip

**Solution:** ถ้าต้องการเห็นการติดตั้งจริง:
```bash
# ลบ dependencies ก่อน
rm -rf vendor node_modules

# รันใหม่
./deploy.sh

# จะเห็นการติดตั้งจริงๆ
```

---

## 📊 Expected Performance

### Fresh Installation
```
Total time: 2-5 minutes (ขึ้นอยู่กับความเร็วอินเทอร์เน็ต)

Breakdown:
- Composer install: 1-2 minutes (95 packages)
- NPM install: 1-2 minutes (431 packages)
- Build assets: 10-30 seconds
- Migrations/Seeders: < 5 seconds
- Optimize: < 5 seconds
```

### Update/Redeploy (with existing deps)
```
Total time: 30-60 seconds

Breakdown:
- Check dependencies: 5 seconds (skip if exists)
- Update .env: < 1 second
- Migrations: < 5 seconds
- Seeders: < 5 seconds (skip if data exists)
- Build assets: 10-30 seconds
- Optimize: < 5 seconds
```

---

## 🎓 What to Look For

### ✅ Good Signs (มันทำงาน)
```
⚠ Composer dependencies not installed
ℹ Installing Composer dependencies...
Installing dependencies from lock file
Package operations: 95 installs, 0 updates, 0 removals
[กำลัง extract packages...]
✓ Composer dependencies installed
```

### ❌ Bad Signs (มันไม่ทำงาน - bug เก่า)
```
✓ Composer dependencies: OK    ← แบบนี้คือ skip ไม่ได้ติดตั้ง
✓ NPM dependencies: OK          ← แบบนี้คือ skip ไม่ได้ติดตั้ง
```

**Note:** ถ้าเห็น "OK" หมายความว่า dependencies มีอยู่แล้ว ถ้าต้องการให้ติดตั้งใหม่ ให้ลบ vendor/ และ node_modules/ ก่อน

---

## 🚀 Next Steps

หลังจากทดสอบแล้ว:

1. **Run Development Server:**
   ```bash
   npm run dev         # Terminal 1
   php artisan serve   # Terminal 2
   ```

2. **Test Admin Login:**
   - URL: http://localhost:8000/login
   - Email: test@example.com
   - Password: password

3. **Test Settings Form:**
   - URL: http://localhost:8000/admin/settings
   - แก้ไขการตั้งค่า
   - กด "บันทึกการตั้งค่า"
   - ตรวจสอบว่าบันทึกได้

---

## 📝 Report Results

หลังทดสอบ กรุณาแจ้งผล:

**✅ ถ้าทำงาน:**
```
✓ deploy.sh ติดตั้ง dependencies จริง
✓ เห็นการติดตั้ง Composer (95 packages)
✓ เห็นการติดตั้ง NPM (431 packages)
✓ ระบบใช้งานได้หลัง deployment
```

**❌ ถ้าไม่ทำงาน:**
```
ปัญหา: [อธิบายปัญหา]
Output: [paste output ของ ./deploy.sh]
Status: [paste output ของ ./deploy.sh status]
```

---

**Happy Testing! 🧪**
