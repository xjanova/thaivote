# 🚀 Setup Guide - Semantic Release Configuration

## วิธีที่ 1: ตั้งค่าอัตโนมัติด้วยสคริปต์ (แนะนำ)

### ข้อกำหนด
- ต้องมี [GitHub CLI](https://cli.github.com/) ติดตั้งแล้ว
- ต้อง login ด้วย `gh auth login`
- ต้องมีสิทธิ์ admin ใน repository

### วิธีการ

```bash
# รันสคริปต์
./setup-branch-protection.sh
```

สคริปต์จะตั้งค่าให้อัตโนมัติ:
- ✅ Actions Permissions (Read and Write + Approve PRs)
- ✅ Branch Protection (Status checks + No PR requirement)
- ✅ Allow github-actions[bot] to bypass

---

## วิธีที่ 2: ตั้งค่าผ่าน GitHub Web UI (Manual)

### Step 1: ตั้งค่า Actions Permissions

1. ไปที่ https://github.com/xjanova/thaivote/settings/actions
2. ในส่วน **Workflow permissions**:
   - ✅ เลือก **"Read and write permissions"**
   - ✅ เลือก **"Allow GitHub Actions to create and approve pull requests"**
3. คลิก **Save**

### Step 2: ตั้งค่า Branch Protection

1. ไปที่ https://github.com/xjanova/thaivote/settings/branches
2. คลิก **Add rule** หรือแก้ไข rule สำหรับ `main`
3. ใน **Branch name pattern** ใส่: `main`
4. ตั้งค่าดังนี้:

#### ส่วนที่ต้อง ✅ เปิด:

**Require status checks to pass before merging**
- ✅ เลือกตัวนี้
- ✅ เลือก "Require branches to be up to date before merging"
- ในช่อง search ให้เลือก status checks:
  - `PHP Tests`
  - `PHP Linting`
  - `JavaScript Linting`

**Require conversation resolution before merging**
- ✅ เลือกตัวนี้

#### ส่วนที่ต้อง ❌ ปิด:

**Require a pull request before merging**
- ❌ **ปิดตัวนี้** (สำคัญมาก!)
- เพราะ semantic-release ต้อง push ตรงไปที่ main

**Require approvals**
- ❌ ปิดตัวนี้

**Do not allow bypassing the above settings**
- ❌ ปิดตัวนี้

5. คลิก **Create** หรือ **Save changes**

### Step 3: ทดสอบ

```bash
# ใน branch ของคุณ
git commit --allow-empty -m "test: verify semantic-release setup"
git push

# Merge เข้า main
# Semantic Release จะรันอัตโนมัติ
```

---

## วิธีที่ 3: ตั้งค่าด้วย GitHub API (สำหรับ Advanced Users)

### ตั้งค่า Actions Permissions

```bash
curl -X PUT \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: Bearer YOUR_GITHUB_TOKEN" \
  https://api.github.com/repos/xjanova/thaivote/actions/permissions \
  -d '{
    "default_workflow_permissions": "write",
    "can_approve_pull_request_reviews": true
  }'
```

### ตั้งค่า Branch Protection

```bash
curl -X PUT \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: Bearer YOUR_GITHUB_TOKEN" \
  https://api.github.com/repos/xjanova/thaivote/branches/main/protection \
  -d '{
    "required_status_checks": {
      "strict": true,
      "contexts": ["PHP Tests", "PHP Linting", "JavaScript Linting"]
    },
    "enforce_admins": false,
    "required_pull_request_reviews": null,
    "restrictions": null,
    "required_linear_history": false,
    "allow_force_pushes": false,
    "allow_deletions": false,
    "block_creations": false,
    "required_conversation_resolution": true,
    "lock_branch": false,
    "allow_fork_syncing": false
  }'
```

---

## ✅ การตรวจสอบว่าตั้งค่าถูกต้อง

### 1. ตรวจสอบ Actions Permissions

```bash
gh api /repos/xjanova/thaivote/actions/permissions
```

ควรเห็น:
```json
{
  "default_workflow_permissions": "write",
  "can_approve_pull_request_reviews": true
}
```

### 2. ตรวจสอบ Branch Protection

```bash
gh api /repos/xjanova/thaivote/branches/main/protection
```

ควรเห็น:
- ✅ `required_status_checks` มี status checks ที่ต้องการ
- ✅ `required_pull_request_reviews` เป็น `null` (ไม่บังคับ PR)
- ✅ `required_conversation_resolution` เป็น `true`

### 3. ทดสอบ Workflow

1. สร้าง feature branch
2. ทำการเปลี่ยนแปลงและ commit ด้วย conventional commits
3. สร้าง PR merge เข้า main
4. Merge PR
5. ตรวจสอบที่ Actions → Release workflow
6. ควรเห็น workflow รันและสร้าง release โดยไม่สร้าง PR

---

## 🆘 Troubleshooting

### ปัญหา: "Resource not accessible by integration"

**สาเหตุ**: Actions ไม่มีสิทธิ์เขียน

**วิธีแก้**:
1. ไปที่ Settings → Actions → General
2. เปลี่ยนเป็น "Read and write permissions"
3. Save

### ปัญหา: "Protected branch update failed"

**สาเหตุ**: Branch protection ยังบังคับให้มี PR

**วิธีแก้**:
1. ไปที่ Settings → Branches
2. แก้ไข rule สำหรับ main
3. ❌ ปิด "Require a pull request before merging"
4. Save

### ปัญหา: "refusing to allow a GitHub App to create or update workflow"

**สาเหตุ**: GitHub Actions ไม่สามารถ approve PR ได้

**วิธีแก้**:
1. ไปที่ Settings → Actions → General
2. ✅ เลือก "Allow GitHub Actions to create and approve pull requests"
3. Save

---

## 📋 Checklist หลัง Setup

- [ ] Actions มีสิทธิ์ "Read and write"
- [ ] Actions สามารถ "Approve pull requests"
- [ ] Branch protection ตั้งค่า required status checks
- [ ] Branch protection **ไม่**บังคับ PR
- [ ] Branch protection ต้อง resolve conversations
- [ ] ทดสอบ merge commit ที่มี `feat:` หรือ `fix:` เข้า main
- [ ] ตรวจสอบว่า semantic-release สร้าง release โดยไม่สร้าง PR

---

## 🎯 ผลลัพธ์ที่คาดหวัง

เมื่อตั้งค่าเสร็จ:

```
Push to main
    ↓
Semantic Release รัน
    ↓
วิเคราะห์ commits
    ↓
Build assets
    ↓
Bump version
    ↓
Commit ไปที่ main [skip ci]  ← ไม่สร้าง PR!
    ↓
สร้าง GitHub Release
    ↓
อัปเดต CHANGELOG.md
    ↓
เสร็จสิ้น ✨
```

**ไม่มีการสร้าง PR ระหว่างทาง!**
