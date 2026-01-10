# GitHub Workflows - ThaiVote

## 📋 ภาพรวม

โปรเจคนี้ใช้ GitHub Actions สำหรับ CI/CD และ release automation โดยมี workflows หลักดังนี้:

### 1. **Release Workflow** (`release.yml`)

ทำงานอัตโนมัติสำหรับการสร้าง release และ versioning

#### การทำงาน

```
1. Push ไปที่ main → Release-Please สร้าง PR พร้อม version bump
2. Auto-approve → Workflow approve PR อัตโนมัติ
3. CI Checks → รัน tests และ linting
4. Auto-merge → Merge PR อัตโนมัติเมื่อ checks ผ่าน
5. Create Release → สร้าง GitHub Release + Tag
6. Build Assets → Build production และสร้าง archive file
```

#### Triggers

- **`push` to `main`**: เมื่อมี code ใหม่ merge เข้า main
  - Release-Please วิเคราะห์ commits และสร้าง PR สำหรับ release

- **`pull_request`**: เมื่อ Release-Please สร้าง PR
  - Auto-approve PR ที่สร้างโดย `github-actions[bot]`
  - Auto-merge หลังจาก checks ผ่าน

- **`workflow_dispatch`**: Manual trigger (สำหรับ force release)

#### Features

✅ **Auto-approve Release PRs**
- ตรวจสอบว่า PR ถูกสร้างโดย `github-actions[bot]`
- ตรวจสอบว่า branch เริ่มต้นด้วย `release-please--`
- Approve อัตโนมัติเพื่อผ่าน branch protection

✅ **Auto-merge Release PRs**
- รอให้ CI checks ผ่านทั้งหมด
- Merge อัตโนมัติด้วย squash merge
- ทำงานหลังจาก auto-approve เสร็จ

✅ **Build Release Assets**
- Build production assets ด้วย npm
- Install dependencies ด้วย composer (production mode)
- สร้าง `.tar.gz` archive พร้อม deploy
- Upload ไปที่ GitHub Releases

#### Branch Protection Requirements

Workflow นี้ทำงานร่วมกับ branch protection โดย:

1. **Required status checks**: CI workflow ต้องผ่าน
2. **Required approvals**: Auto-approve job จะ approve ให้
3. **No need for admin bypass**: ใช้ standard GITHUB_TOKEN

#### Version Bumping

Release-Please วิเคราะห์ commit messages แบบ Conventional Commits:

- `feat:` → Minor version bump (1.0.0 → 1.1.0)
- `fix:` → Patch version bump (1.0.0 → 1.0.1)
- `feat!:` หรือ `BREAKING CHANGE:` → Major version bump (1.0.0 → 2.0.0)

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

แนะนำให้ตั้งค่า main branch protection ดังนี้:

```yaml
# Settings → Branches → Branch protection rules → main

✅ Require a pull request before merging
  ✅ Require approvals: 1
  ✅ Dismiss stale pull request approvals when new commits are pushed

✅ Require status checks to pass before merging
  ✅ Require branches to be up to date before merging
  Required status checks:
    - PHP Tests
    - PHP Linting
    - JavaScript Linting

✅ Require conversation resolution before merging

❌ Do not require administrator bypass
   (ให้ workflow ใช้ GITHUB_TOKEN ได้)
```

### Secrets

ไม่จำเป็นต้องตั้ง secrets เพิ่มเติม เพราะใช้ `GITHUB_TOKEN` ที่ GitHub สร้างให้อัตโนมัติ

หาก branch protection ต้องการ admin bypass จะต้องสร้าง Personal Access Token (PAT):

1. สร้าง Fine-grained PAT ที่ https://github.com/settings/tokens
2. Permissions:
   - Contents: Read and write
   - Pull requests: Read and write
   - Workflows: Read and write
3. เพิ่ม secret `PAT_TOKEN` ใน repository settings
4. แก้ `token: ${{ secrets.GITHUB_TOKEN }}` → `token: ${{ secrets.PAT_TOKEN }}`

---

## 📝 การใช้งาน

### การสร้าง Release แบบ Auto

1. พัฒนา feature ใน branch แยก
2. สร้าง PR merge เข้า main
3. เขียน commit message ตาม Conventional Commits:
   ```bash
   feat: add new election map feature
   fix: resolve vote counting bug
   ```
4. Merge PR เข้า main
5. Release-Please จะสร้าง Release PR อัตโนมัติ
6. CI checks จะรัน
7. Workflow จะ approve และ merge PR อัตโนมัติ
8. GitHub Release จะถูกสร้างพร้อม `.tar.gz` file

### การสร้าง Release แบบ Manual

1. ไปที่ Actions → Release workflow
2. คลิก "Run workflow"
3. เลือก release type:
   - `auto`: ให้ Release-Please วิเคราะห์ commits
   - `patch`: Force patch version bump
   - `minor`: Force minor version bump
   - `major`: Force major version bump

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

### PR ไม่ถูก auto-approve

**อาการ**: Release PR ถูกสร้างแต่ไม่มี approval

**สาเหตุ**: GITHUB_TOKEN อาจไม่มีสิทธิ์ approve

**วิธีแก้**:
1. ตรวจสอบว่า workflow file มี `permissions: pull-requests: write`
2. ตรวจสอบ Settings → Actions → General → Workflow permissions
   - เลือก "Read and write permissions"
   - ✅ "Allow GitHub Actions to create and approve pull requests"

### PR ไม่ auto-merge

**อาการ**: PR ถูก approve แต่ไม่ merge

**สาเหตุ**:
1. CI checks ยังไม่ผ่าน
2. Branch ไม่ up-to-date กับ main
3. Auto-merge ไม่ถูกเปิดใช้งานใน repository settings

**วิธีแก้**:
1. รอให้ CI checks ผ่านทั้งหมด
2. ตรวจสอบ Settings → General → Pull Requests
   - ✅ "Allow auto-merge"

### Workflow ไม่ทำงาน

**อาการ**: Push ไป main แล้วไม่มี workflow รัน

**สาเหตุ**: Workflow file มี syntax error

**วิธีแก้**:
1. ตรวจสอบ syntax ที่ Actions → ชื่อ workflow
2. ตรวจสอบว่า workflow file อยู่ใน `.github/workflows/`
3. ตรวจสอบว่าไฟล์มีนามสกุล `.yml` หรือ `.yaml`

---

## 📚 เอกสารเพิ่มเติม

- [Release-Please Documentation](https://github.com/googleapis/release-please)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Branch Protection Rules](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches)
