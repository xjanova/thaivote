# ThaiVote - ระบบรายงานผลเลือกตั้งประเทศไทยแบบเรียลไทม์

ระบบรายงานผลเลือกตั้งแบบสด (Real-time) ที่รวบรวมข้อมูลจากหลายแหล่ง พร้อมระบบ AI สำหรับรวบรวมข่าวอัตโนมัติ และรองรับการลงคะแนนด้วย Blockchain ในอนาคต

## Features

### 1. Real-time Election Results Dashboard
- แสดงผลคะแนนแบบเรียลไทม์ผ่าน WebSocket
- แผนที่ประเทศไทยแบบ Interactive (ซูมเข้าออก, เลื่อน, คลิกดูรายละเอียด)
- แบ่งตามจังหวัด, เขตเลือกตั้ง
- Animation สวยงามทันสมัย

### 2. Multi-source Data Aggregator
- รวบรวมข้อมูลจากหลายแหล่ง (กกต., สำนักข่าว)
- เปรียบเทียบและหา consensus จากหลายแหล่ง
- คำนวณความน่าเชื่อถือของแต่ละแหล่ง

### 3. AI News Aggregator
- ดึงข่าวจาก RSS, API และ Web Scraping
- วิเคราะห์ความเกี่ยวข้องกับการเลือกตั้งด้วย AI
- วิเคราะห์ sentiment (positive/negative/neutral)
- จับคีย์เวิร์ดที่กำหนดอัตโนมัติ

### 4. Party Integration API
- API สำหรับพรรคการเมืองเชื่อมต่อ
- ลิงค์กับ Social Media ของแต่ละพรรค
- Dashboard สำหรับพรรคดูสถิติ

### 5. Blockchain Voting (Future)
- ลงคะแนนแบบปลอดภัยด้วย Blockchain
- Zero-Knowledge Proof สำหรับความเป็นส่วนตัว
- ตรวจสอบย้อนหลังได้

### 6. Admin Panel
- จัดการการเลือกตั้ง
- จัดการพรรคและผู้สมัคร
- จัดการแหล่งข้อมูลและคีย์เวิร์ด
- ดูสถิติและ logs

### 7. Android App
- Native Android app ด้วย Kotlin + Jetpack Compose
- ดูผลเลือกตั้งแบบเรียลไทม์
- แผนที่และข่าวสาร

## Tech Stack

### Backend
- **Laravel 11** - PHP Framework
- **Laravel Reverb** - WebSocket Server
- **Laravel Sanctum** - API Authentication
- **MySQL/PostgreSQL** - Database
- **Redis** - Caching & Queue

### Frontend
- **Vue.js 3** - JavaScript Framework
- **Inertia.js** - SPA without API
- **Tailwind CSS** - Styling
- **D3.js** - Map Visualization
- **Chart.js** - Charts

### Mobile
- **Kotlin** - Android Development
- **Jetpack Compose** - UI Framework
- **Hilt** - Dependency Injection
- **Retrofit** - HTTP Client

## Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+ or PostgreSQL 14+
- Redis

### Backend Setup

```bash
# Clone repository
git clone https://github.com/your-repo/thaivote.git
cd thaivote

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env

# Run migrations
php artisan migrate

# Install Node dependencies
npm install

# Build assets
npm run build

# Start development server
php artisan serve
```

### WebSocket Server

```bash
php artisan reverb:start
```

### Queue Worker

```bash
php artisan queue:work
```

## API Documentation

### Public Endpoints

```
GET /api/elections                    - List all elections
GET /api/elections/active             - Get active election
GET /api/elections/{id}               - Get election details
GET /api/provinces                    - List provinces
GET /api/provinces/geojson            - Get GeoJSON for map
GET /api/parties                      - List parties
GET /api/news                         - List news
GET /api/news/breaking                - Get breaking news
```

### Party API (Authenticated)

```
POST /api/party-api/authenticate      - Get access token
GET  /api/party-api/profile           - Get party profile
POST /api/party-api/results           - Submit results
GET  /api/party-api/analytics         - Get analytics
```

## Directory Structure

```
thaivote/
├── app/
│   ├── Events/           # Broadcasting events
│   ├── Http/Controllers/ # Controllers
│   ├── Jobs/             # Queue jobs
│   ├── Models/           # Eloquent models
│   └── Services/         # Business logic
├── database/
│   └── migrations/       # Database migrations
├── resources/
│   ├── css/              # Stylesheets
│   └── js/
│       ├── components/   # Vue components
│       ├── layouts/      # Layout components
│       ├── pages/        # Page components
│       └── stores/       # Pinia stores
├── routes/
│   ├── api.php           # API routes
│   └── web.php           # Web routes
└── android-app/          # Android application
```

## License

This project is licensed under the MIT License.
