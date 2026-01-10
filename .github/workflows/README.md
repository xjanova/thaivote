# GitHub Workflows - ThaiVote

## 📋 ภาพรวม

โปรเจคนี้ใช้ GitHub Actions สำหรับ CI/CD และ release automation โดยมี workflows หลักดังนี้:

### 1. **Release Workflow** (`release.yml`)

ทำงานอัตโนมัติสำหรับการสร้าง release และ versioning โดยใช้ **Semantic Release**

#### การทำงาน

```
1. Push ไปที่ main
2. Semantic Release วิเคราะห์ commits
3. Build production assets
4. Create release archive (.tar.gz)
5. Bump version และ commit กลับไปที่ main (ไม่สร้าง PR)
6. สร้าง GitHub Release + Tag
7. อัปเดต CHANGELOG.md
```

#### Triggers

- **`push` to `main`**: เมื่อมี code ใหม่ merge เข้า main
  - Semantic Release วิเคราะห์ commits และ bump version อัตโนมัติ
  - **ไม่สร้าง PR** - commit version bump ตรงไปที่ main

- **`workflow_dispatch`**: Manual trigger (สำหรับ force release)

#### Features

✅ **ไม่สร้าง PR**
- Commit version bump ตรงไปที่ main branch
- ไม่มี release branch หรือ PR ระหว่างทาง
- Release process เร็วกว่า

✅ **Auto Version Bumping**
- วิเคราะห์ commit messages แบบ Conventional Commits
- Bump version อัตโนมัติตามประเภทของการเปลี่ยนแปลง
- อัปเดต `package.json`, `composer.json` (ถ้ามี)

✅ **CHANGELOG Generation**
- สร้าง `CHANGELOG.md` อัตโนมัติ
- จัดหมวดหมู่ตาม commit types
- รวม breaking changes และ deprecations

✅ **Build Release Assets**
- Build production assets ด้วย npm
- Install dependencies ด้วย composer (production mode)
- สร้าง `.tar.gz` archive พร้อม deploy
- Upload ไปที่ GitHub Releases

#### Branch Protection Compatibility

⚠️ **สำคัญ:** Semantic Release ต้อง **push ตรงไปที่ main** เพื่อ commit version bump

หากคุณมี branch protection บน main ที่ **ต้องการ PR ก่อน push** จะต้องตั้งค่าดังนี้:

**ตัวเลือกที่ 1: Allow GitHub Actions bypass (แนะนำ)**

Settings → Branches → Branch protection rules → main:
- ☑️ ใน "Restrict pushes that create matching branches"
- เพิ่ม `github-actions[bot]` ใน "Allow bypasses"

**ตัวเลือกที่ 2: ไม่ใช้ "Require a pull request before merging"**

ถ้าต้องการใช้ branch protection แต่ไม่บังคับ PR:
- ✅ Require status checks to pass before merging
- ✅ Require conversation resolution before merging
- ❌ Require a pull request before merging (ปิดตัวนี้)

#### Version Bumping Rules

Semantic Release วิเคราะห์ commit messages แบบ Conventional Commits:

- `feat:` → Minor version bump (1.0.0 → 1.1.0)
- `fix:` → Patch version bump (1.0.0 → 1.0.1)
- `perf:` → Patch version bump
- `refactor:` → Patch version bump
- `feat!:` หรือ `BREAKING CHANGE:` → Major version bump (1.0.0 → 2.0.0)
- `docs:`, `style:`, `test:`, `chore:` → ไม่ bump version

---

### 2. **CI Workflow** (`ci.yml`)

ทำงานอัตโนมัติสำหรับการทดสอบและตรวจสอบ code quality

#### Triggers

- **`push`**: ทุก push ไปที่ทุก branch
- **`pull_request`**: ทุก PR

#### Jobs

1. **PHP Tests**: รัน PHPUnit tests
2. **PHP Linting**: ตรวจสอบ code style ด้วย Laravel Pint
3. **JavaScript Linting**: ตรวจสอบด้วย ESLint

---

### 3. **Deploy Workflow** (`deploy.yml`)

สำหรับ deploy ไปยัง production server (ถ้ามี)

---

## 🔧 การตั้งค่า

### Branch Protection Rules

⚠️ **สำคัญ:** เนื่องจากใช้ Semantic Release ที่ต้อง commit ตรงไปที่ main

**ตัวเลือกที่ 1: อนุญาตให้ GitHub Actions bypass (แนะนำ)**

```yaml
# Settings → Branches → Branch protection rules → main

✅ Require status checks to pass before merging
  ✅ Require branches to be up to date before merging
  Required status checks:
    - PHP Tests
    - PHP Linting
    - JavaScript Linting

✅ Require conversation resolution before merging

✅ Restrict who can push to matching branches
  - เพิ่ม "github-actions[bot]" ใน allowed actors
  หรือ
  - เลือก "Allow specified actors to bypass required pull requests"
  - เพิ่ม "github-actions[bot]"
```

**ตัวเลือกที่ 2: ไม่บังคับ PR (แนะนำถ้าต้องการ simple setup)**

```yaml
# Settings → Branches → Branch protection rules → main

❌ Require a pull request before merging (ปิดตัวนี้)

✅ Require status checks to pass before merging
  ✅ Require branches to be up to date before merging
  Required status checks:
    - PHP Tests
    - PHP Linting
    - JavaScript Linting

✅ Require conversation resolution before merging
```

### Secrets

ไม่จำเป็นต้องตั้ง secrets เพิ่มเติม เพราะใช้ `GITHUB_TOKEN` ที่ GitHub สร้างให้อัตโนมัติ

### Actions Permissions

ตรวจสอบว่า Actions มีสิทธิ์เพียงพอ:

Settings → Actions → General → Workflow permissions:
- ✅ เลือก "Read and write permissions"

---

## 📝 การใช้งาน

### การสร้าง Release อัตโนมัติ

1. พัฒนา feature ใน branch แยก
2. สร้าง PR merge เข้า main
3. **เขียน commit message ตาม Conventional Commits:**
   ```bash
   feat: add new election map feature
   fix: resolve vote counting bug
   perf: improve database query performance
   refactor: restructure voting logic
   ```
4. Merge PR เข้า main
5. ✅ **Semantic Release จะทำงานอัตโนมัติทันที:**
   - วิเคราะห์ commits ตั้งแต่ release ก่อนหน้า
   - คำนวณ version ใหม่
   - Build production assets
   - สร้าง `.tar.gz` archive
   - Commit version bump กลับไปที่ main (ไม่สร้าง PR)
   - สร้าง GitHub Release + Tag
   - อัปเดต CHANGELOG.md

### Commit Message Format

ต้องใช้ **Conventional Commits** format:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**ตัวอย่าง:**

```bash
# Minor version bump (1.0.0 → 1.1.0)
feat: add real-time vote tracking
feat(map): implement province coloring

# Patch version bump (1.0.0 → 1.0.1)
fix: resolve vote counting error
fix(api): handle null response from ECT
perf: optimize database queries
refactor: simplify election logic

# Major version bump (1.0.0 → 2.0.0)
feat!: redesign voting system
feat: change API endpoint structure

BREAKING CHANGE: API endpoints now use /v2/ prefix

# ไม่ bump version
docs: update README
style: format code with Pint
test: add unit tests for VoteController
chore: update dependencies
```

### การสร้าง Release แบบ Manual

1. ไปที่ Actions → Release workflow
2. คลิก "Run workflow"
3. เลือก branch "main"
4. คลิก "Run workflow"
5. Workflow จะวิเคราะห์ commits และสร้าง release อัตโนมัติ

### การ Deploy

1. ดาวน์โหลด `.tar.gz` file จาก GitHub Releases
2. อัปโหลดไปยัง server
3. แตกไฟล์:
   ```bash
   tar -xzvf thaivote-X.Y.Z.tar.gz
   cd thaivote
   ```
4. รัน deployment script:
   ```bash
   ./deploy.sh
   ```

---

## 🔍 Troubleshooting

### Semantic Release ไม่สร้าง Release

**อาการ**: Push ไป main แล้วไม่มี release ถูกสร้าง

**สาเหตุ**:
1. ไม่มี commits ที่ควร bump version (เช่น มีแต่ `docs:`, `chore:`)
2. Commit messages ไม่ตาม Conventional Commits format
3. ไม่มีการเปลี่ยนแปลงตั้งแต่ release ล่าสุด

**วิธีแก้**:
1. ตรวจสอบ commit messages ว่าเขียนตาม Conventional Commits
2. ตรวจสอบ Actions logs ว่า semantic-release ทำงานหรือไม่
3. ตรวจสอบว่ามี tags อยู่ใน repository (ถ้าไม่มี release ก่อนหน้า ต้องสร้าง tag แรกเอง)

### Push ถูก Reject โดย Branch Protection

**อาการ**: Semantic Release fail ด้วย error "protected branch hook declined"

**สาเหตุ**: Branch protection ไม่อนุญาตให้ GitHub Actions push ตรงไปที่ main

**วิธีแก้**:
1. ตั้งค่าตามหัวข้อ "Branch Protection Rules" ด้านบน
2. เพิ่ม `github-actions[bot]` ใน allowed actors
3. หรือปิด "Require a pull request before merging"

### Workflow ไม่ทำงาน

**อาการ**: Push ไป main แล้วไม่มี workflow รัน

**สาเหตุ**:
1. Workflow file มี syntax error
2. Commit message มี `[skip ci]` tag

**วิธีแก้**:
1. ตรวจสอบ syntax ที่ Actions → Release workflow
2. ตรวจสอบว่า workflow file อยู่ใน `.github/workflows/`
3. ตรวจสอบว่าไฟล์มีนามสกุล `.yml` หรือ `.yaml`
4. ตรวจสอบ commit message ว่าไม่มี `[skip ci]`

### ไม่มี Release Tag

**อาการ**: ครั้งแรกที่รัน semantic-release ไม่มี tag

**สาเหตุ**: Repository ยังไม่มี tag ใดๆ

**วิธีแก้**: สร้าง initial tag ด้วยตนเอง
```bash
git tag v1.0.0
git push origin v1.0.0
```

จากนั้น semantic-release จะ bump version จาก v1.0.0 ไปต่อ

### Permission Denied

**อาการ**: Workflow fail ด้วย "Permission denied" หรือ "403"

**สาเหตุ**: GITHUB_TOKEN ไม่มีสิทธิ์เพียงพอ

**วิธีแก้**:
1. Settings → Actions → General → Workflow permissions
2. เลือก "Read and write permissions"
3. บันทึกการเปลี่ยนแปลง

---

## 📚 เอกสารเพิ่มเติม

- [Semantic Release Documentation](https://semantic-release.gitbook.io/)
- [Conventional Commits Specification](https://www.conventionalcommits.org/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Branch Protection Rules](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches)

---

## 🔄 Migration จาก Release-Please

หากคุณเคยใช้ Release-Please อยู่ก่อน:

1. **ไม่ต้องกังวล** - Semantic Release จะอ่าน tags ที่มีอยู่และ bump ต่อจากนั้น
2. **ลบ Release-Please PR** ที่ค้างอยู่ (ถ้ามี)
3. **ปรับ branch protection** ตามคำแนะนำด้านบน
4. Merge commit ใหม่เข้า main ด้วย conventional commits
5. Semantic Release จะสร้าง release ใหม่อัตโนมัติ

**ข้อดีของ Semantic Release เทียบกับ Release-Please:**
- ✅ ไม่สร้าง PR (release เร็วกว่า)
- ✅ CHANGELOG สวยงามกว่า (มี emoji + categorized)
- ✅ Config ยืดหยุ่นกว่า (ปรับแต่งได้เยอะ)
- ✅ Plugin ecosystem ใหญ่กว่า
