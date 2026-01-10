#!/bin/bash
# Setup Branch Protection และ Actions Permissions สำหรับ Semantic Release

set -e

REPO_OWNER="xjanova"
REPO_NAME="thaivote"
BRANCH="main"

echo "🔧 กำลังตั้งค่า Branch Protection และ Actions Permissions..."
echo ""

# ตรวจสอบว่ามี gh CLI หรือไม่
if ! command -v gh &> /dev/null; then
    echo "❌ ไม่พบ GitHub CLI (gh)"
    echo "📦 กรุณาติดตั้งก่อน: https://cli.github.com/"
    echo ""
    echo "หรือติดตั้งด้วยคำสั่ง:"
    echo "  macOS: brew install gh"
    echo "  Linux: sudo apt install gh"
    echo ""
    exit 1
fi

# ตรวจสอบว่า login แล้วหรือยัง
if ! gh auth status &> /dev/null; then
    echo "🔑 กรุณา login ก่อน:"
    gh auth login
fi

echo "1️⃣ กำลังตั้งค่า Actions Permissions..."
gh api \
  --method PUT \
  -H "Accept: application/vnd.github+json" \
  /repos/${REPO_OWNER}/${REPO_NAME}/actions/permissions \
  -f default_workflow_permissions='write' \
  -F can_approve_pull_request_reviews=true

echo "✅ ตั้งค่า Actions Permissions เรียบร้อย"
echo ""

echo "2️⃣ กำลังตั้งค่า Branch Protection..."

# ลบ branch protection เดิม (ถ้ามี)
gh api \
  --method DELETE \
  -H "Accept: application/vnd.github+json" \
  /repos/${REPO_OWNER}/${REPO_NAME}/branches/${BRANCH}/protection \
  2>/dev/null || true

# สร้าง branch protection ใหม่
gh api \
  --method PUT \
  -H "Accept: application/vnd.github+json" \
  /repos/${REPO_OWNER}/${REPO_NAME}/branches/${BRANCH}/protection \
  -f required_status_checks='{"strict":true,"contexts":["PHP Tests","PHP Linting","JavaScript Linting"]}' \
  -f enforce_admins=false \
  -f required_pull_request_reviews=null \
  -f restrictions='{"users":[],"teams":[],"apps":[]}' \
  -f required_linear_history=false \
  -f allow_force_pushes=false \
  -f allow_deletions=false \
  -f block_creations=false \
  -f required_conversation_resolution=true \
  -f lock_branch=false \
  -f allow_fork_syncing=false

echo "✅ ตั้งค่า Branch Protection เรียบร้อย"
echo ""

echo "3️⃣ กำลังเพิ่ม github-actions[bot] เป็น bypass actor..."

# เพิ่ม github-actions[bot] ให้สามารถ bypass push restrictions
gh api \
  --method POST \
  -H "Accept: application/vnd.github+json" \
  /repos/${REPO_OWNER}/${REPO_NAME}/branches/${BRANCH}/protection/restrictions \
  -f users='[]' \
  -f teams='[]' \
  -f apps='["github-actions"]' \
  2>/dev/null || echo "⚠️  ไม่สามารถตั้งค่า bypass actor ได้ (อาจต้องเป็น Pro/Enterprise account)"

echo ""
echo "✨ เสร็จสิ้น! การตั้งค่าทั้งหมดเรียบร้อยแล้ว"
echo ""
echo "📋 สรุปการตั้งค่า:"
echo "  ✅ Actions มีสิทธิ์ Read and Write"
echo "  ✅ Actions สามารถ approve PR ได้"
echo "  ✅ Branch protection ต้องผ่าน status checks"
echo "  ✅ ไม่บังคับ PR (อนุญาตให้ push ตรง)"
echo "  ✅ ต้อง resolve conversations"
echo ""
echo "🚀 ตอนนี้ Semantic Release พร้อมใช้งานแล้ว!"
