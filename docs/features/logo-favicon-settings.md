# Logo and Favicon Settings Feature

## Overview
ฟีเจอร์นี้ช่วยให้แอดมินสามารถตั้งค่าโลโก้เว็บไซต์และ favicon ได้ผ่านหน้า Admin Settings

## Files Modified

### Backend Files

1. **database/migrations/2026_01_10_064246_add_logo_and_favicon_settings.php**
   - เพิ่ม settings สำหรับ `site_logo` และ `site_favicon`
   - Group: `appearance`
   - Type: `string` (เก็บ path ของไฟล์)

2. **app/Http/Controllers/Admin/SettingsController.php**
   - เพิ่ม validation rules สำหรับ `site_logo` และ `site_favicon`
   - เพิ่ม method `uploadImage()` สำหรับอัปโหลดรูปภาพ
   - จัดการลบรูปเก่าเมื่ออัปโหลดรูปใหม่
   - Logo: รองรับ PNG, JPG, JPEG, SVG (สูงสุด 2MB)
   - Favicon: รองรับ PNG, JPG, JPEG, SVG, ICO (สูงสุด 1MB)

3. **routes/web.php**
   - เพิ่ม route `GET /admin/settings/api` สำหรับ API endpoint

4. **resources/views/app.blade.php**
   - เพิ่ม dynamic favicon ที่อ่านจาก settings
   - Fallback ไปที่ `favicon.ico` ถ้าไม่มี custom favicon

### Frontend Files

1. **resources/js/pages/Admin/Settings.vue**
   - เพิ่มส่วน "รูปลักษณ์" (Appearance) ด้านบนสุดของฟอร์ม
   - เพิ่ม file upload fields สำหรับ logo และ favicon
   - แสดง preview รูปปัจจุบัน
   - จัดการ file upload ผ่าน `handleLogoChange()` และ `handleFaviconChange()`

2. **resources/js/layouts/AdminLayout.vue**
   - แสดงโลโก้แบบ dynamic ในส่วน sidebar
   - Fetch settings จาก API เมื่อ component mount
   - Fallback ไปที่ชื่อเว็บไซต์ถ้าไม่มีโลโก้

## Usage

### สำหรับ Admin

1. เข้าไปที่ **Admin > ตั้งค่า**
2. ในส่วน **รูปลักษณ์**:
   - **โลโก้เว็บไซต์**: อัปโหลดโลโก้ (รองรับ PNG, JPG, JPEG, SVG สูงสุด 2MB)
   - **Favicon**: อัปโหลด favicon (รองรับ PNG, JPG, JPEG, SVG, ICO สูงสุด 1MB)
3. กดปุ่ม **บันทึกการตั้งค่า**
4. โลโก้จะแสดงใน Admin Sidebar ทันที
5. Favicon จะแสดงใน browser tab

### File Storage

- ไฟล์จะถูกเก็บใน `storage/app/public/images/settings/`
- ไฟล์จะมีชื่อแบบ UUID เพื่อป้องกันการชนกัน
- เข้าถึงได้ผ่าน `/storage/images/settings/{filename}`

### API Endpoint

```
GET /admin/settings/api
```

Response:
```json
{
    "success": true,
    "data": {
        "site_name": "ThaiVote",
        "site_logo": "images/settings/abc-123.png",
        "site_favicon": "images/settings/def-456.ico",
        ...
    }
}
```

## Technical Details

### Validation Rules

**Logo:**
- File types: `png`, `jpg`, `jpeg`, `svg`
- Max size: 2048 KB (2 MB)
- Validation: `nullable|image|mimes:png,jpg,jpeg,svg|max:2048`

**Favicon:**
- File types: `png`, `jpg`, `jpeg`, `svg`, `ico`
- Max size: 1024 KB (1 MB)
- Validation: `nullable|image|mimes:png,jpg,jpeg,svg,ico|max:1024`

### Database Schema

Settings table มี columns:
- `key`: `site_logo` หรือ `site_favicon`
- `value`: path ของไฟล์ (เช่น `images/settings/uuid.png`)
- `type`: `string`
- `group`: `appearance`

### Caching

- Settings ถูก cache ไว้ 1 ชั่วโมง (3600 วินาที)
- Cache จะถูกล้างอัตโนมัติเมื่อมีการอัปเดต settings
- Cache key: `setting.{key}` และ `settings.all`

## Migration

รัน migration:
```bash
php artisan migrate
```

Migration จะเพิ่ม 2 settings:
- `site_logo` (default: empty string)
- `site_favicon` (default: empty string)

## Rollback

หากต้องการ rollback:
```bash
php artisan migrate:rollback --step=1
```

จะลบ settings `site_logo` และ `site_favicon` ออกจากตาราง

## Security

- ใช้ UUID สำหรับชื่อไฟล์เพื่อป้องกัน path traversal
- Validate file types และขนาดไฟล์
- ลบไฟล์เก่าอัตโนมัติเมื่ออัปโหลดไฟล์ใหม่
- ใช้ Laravel's Storage facade สำหรับจัดการไฟล์

## Future Improvements

- [ ] Image cropping/resizing
- [ ] Multiple logo variants (light/dark theme)
- [ ] Logo dimensions validation
- [ ] Favicon auto-generation from logo
- [ ] Preview ในหน้า public
