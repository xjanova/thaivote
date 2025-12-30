# CLAUDE.md - คู่มือการพัฒนา ThaiVote

## 📋 ภาพรวมโปรเจค

**ThaiVote** คือระบบรายงานผลเลือกตั้งแบบเรียลไทม์สำหรับประเทศไทย

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue.js 3 + Inertia.js + Tailwind CSS
- **Database**: MySQL 8.0+ / SQLite
- **Real-time**: Laravel Reverb (WebSocket)
- **Queue**: Laravel Queue + Redis
- **Public Directory**: `public_html` (สำหรับ shared hosting)

---

## 🏗️ โครงสร้างโปรเจค

```
thaivote/
├── app/
│   ├── Http/Controllers/     # Controllers (ใช้ Resource Controllers)
│   │   ├── Api/              # API Controllers (return JSON)
│   │   ├── Admin/            # Admin Controllers
│   │   └── Install/          # Installation Wizard
│   ├── Models/               # Eloquent Models
│   ├── Services/             # Business Logic Services
│   ├── Events/               # Event Classes
│   ├── Jobs/                 # Queue Jobs
│   └── Policies/             # Authorization Policies
├── database/
│   ├── migrations/           # Database Migrations
│   └── seeders/              # Database Seeders
├── resources/
│   ├── js/
│   │   ├── components/       # Vue Components
│   │   │   ├── map/          # Map Components
│   │   │   └── admin/        # Admin Components
│   │   ├── pages/            # Inertia Pages
│   │   ├── layouts/          # Layout Components
│   │   ├── stores/           # Pinia Stores
│   │   └── data/             # Static Data (provinces, parties)
│   └── views/
│       └── install/          # Blade Views (Installation Wizard)
├── routes/
│   ├── web.php               # Web Routes
│   ├── api.php               # API Routes
│   └── channels.php          # WebSocket Channels
├── public_html/              # Web Root (ใช้แทน public/)
│   ├── index.php             # Entry Point
│   ├── build/                # Vite Build Output
│   └── storage/              # Symlink to storage/app/public
├── docs/                     # Documentation
└── tests/                    # Tests
```

> **หมายเหตุ**: โปรเจคนี้ใช้ `public_html` แทน `public` เพื่อรองรับ shared hosting (DirectAdmin, cPanel)
> การตั้งค่าอยู่ใน `bootstrap/app.php` ด้วย `usePublicPath()`

---

## 📏 กฎการพัฒนา (Development Rules)

### 1. Naming Conventions

#### PHP (Laravel)
```php
// Controllers: PascalCase + Controller suffix
class ElectionController extends Controller {}
class ProvinceResultController extends Controller {}

// Models: Singular PascalCase
class Election extends Model {}
class ProvinceResult extends Model {}

// Migrations: snake_case with timestamp
2024_01_01_000001_create_elections_table.php

// Methods: camelCase
public function getActiveElection() {}
public function calculateResults() {}

// Variables: camelCase
$electionResults = [];
$totalVotes = 0;
```

#### JavaScript/Vue
```javascript
// Components: PascalCase
ThailandMap.vue
ProvinceDetail.vue
ElectionResults.vue

// Composables: camelCase with 'use' prefix
useElectionStore()
useResultsData()

// Variables/Functions: camelCase
const electionId = ref(1)
function fetchResults() {}

// Constants: UPPER_SNAKE_CASE
const API_BASE_URL = '/api'
const MAX_RETRY_COUNT = 3
```

#### Files & Directories
```
# Components: PascalCase
components/map/ThailandMap.vue
components/admin/StatCard.vue

# Data files: camelCase
data/provinces.js
data/constituencies.js

# Config files: kebab-case
.php-cs-fixer.php
eslint.config.js
```

### 2. Code Organization

#### Controllers
```php
// ✅ ถูกต้อง: Thin Controllers, Fat Services
class ElectionController extends Controller
{
    public function __construct(
        private ElectionService $electionService
    ) {}

    public function show(Election $election)
    {
        return $this->electionService->getElectionWithResults($election);
    }
}

// ❌ ผิด: Business logic ใน Controller
class ElectionController extends Controller
{
    public function show(Election $election)
    {
        $results = ProvinceResult::where('election_id', $election->id)
            ->with(['party', 'province'])
            ->get()
            ->groupBy('province_id');
        // ... complex logic
    }
}
```

#### Services
```php
// Services อยู่ใน app/Services/
// แต่ละ Service รับผิดชอบ domain เดียว

app/Services/
├── ElectionService.php       # Election operations
├── ResultService.php         # Result calculations
├── NewsAggregatorService.php # News fetching & AI
├── ResultScraperService.php  # Multi-source scraping
└── BlockchainVotingService.php # Future blockchain
```

#### Vue Components
```vue
<!-- ✅ ถูกต้อง: Single Responsibility -->
<script setup>
// Imports
import { ref, computed, onMounted } from 'vue'
import { useResultsStore } from '@/stores/results'

// Props & Emits
const props = defineProps({...})
const emit = defineEmits([...])

// Composables/Stores
const store = useResultsStore()

// Reactive State
const isLoading = ref(false)

// Computed
const sortedResults = computed(() => ...)

// Methods
function handleClick() {...}

// Lifecycle
onMounted(() => {...})
</script>

<template>
  <!-- Template -->
</template>

<style scoped>
/* Scoped styles */
</style>
```

### 3. API Design

#### RESTful Endpoints
```
# Resources
GET    /api/elections              # List elections
GET    /api/elections/{id}         # Show election
POST   /api/elections              # Create election (admin)
PUT    /api/elections/{id}         # Update election (admin)
DELETE /api/elections/{id}         # Delete election (admin)

# Nested Resources
GET    /api/elections/{id}/results           # Election results
GET    /api/elections/{id}/provinces         # Province results
GET    /api/provinces/{id}/constituencies    # Constituencies

# Actions
POST   /api/elections/{id}/publish           # Publish results
POST   /api/results/recalculate              # Recalculate
```

#### Response Format
```json
// Success Response
{
    "success": true,
    "data": { ... },
    "meta": {
        "current_page": 1,
        "total": 100
    }
}

// Error Response
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": ["Error message"]
    }
}
```

### 4. Database Conventions

#### Migrations
```php
// ✅ ใช้ foreign key constraints
$table->foreignId('election_id')->constrained()->cascadeOnDelete();

// ✅ ใช้ indexes สำหรับ columns ที่ query บ่อย
$table->index(['election_id', 'province_id']);

// ✅ ใช้ timestamps
$table->timestamps();
$table->softDeletes(); // ถ้าต้องการ soft delete
```

#### Models
```php
// ✅ ระบุ fillable/guarded
protected $fillable = ['name', 'status'];

// ✅ ระบุ casts
protected $casts = [
    'results' => 'array',
    'published_at' => 'datetime',
    'is_active' => 'boolean',
];

// ✅ ใช้ relationships
public function province(): BelongsTo
{
    return $this->belongsTo(Province::class);
}
```

### 5. Frontend State Management

#### Pinia Stores
```javascript
// stores/results.js
export const useResultsStore = defineStore('results', () => {
    // State
    const results = ref([])
    const isLoading = ref(false)

    // Getters
    const totalVotes = computed(() =>
        results.value.reduce((sum, r) => sum + r.votes, 0)
    )

    // Actions
    async function fetchResults(electionId) {
        isLoading.value = true
        try {
            const response = await axios.get(`/api/elections/${electionId}/results`)
            results.value = response.data.data
        } finally {
            isLoading.value = false
        }
    }

    return { results, isLoading, totalVotes, fetchResults }
})
```

### 6. Real-time Updates

#### Broadcasting Events
```php
// Events ต้อง implement ShouldBroadcast
class ResultsUpdated implements ShouldBroadcast
{
    public function __construct(
        public Election $election,
        public array $results
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('election.' . $this->election->id);
    }
}
```

#### Frontend Listening
```javascript
// ใช้ Echo สำหรับ listen events
Echo.channel(`election.${electionId}`)
    .listen('ResultsUpdated', (event) => {
        store.updateResults(event.results)
    })
```

---

## 🔧 Commands ที่ใช้บ่อย

```bash
# Development
php artisan serve              # Start Laravel server
npm run dev                    # Start Vite dev server
php artisan reverb:start       # Start WebSocket server
php artisan queue:work         # Start queue worker

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh --seed  # Reset & seed
php artisan db:seed            # Run seeders

# Cache
php artisan cache:clear        # Clear cache
php artisan config:cache       # Cache config (production)
php artisan route:cache        # Cache routes (production)

# Code Quality
./vendor/bin/pint              # PHP code style (Laravel Pint)
npm run lint                   # ESLint
npm run format                 # Prettier

# Testing
php artisan test               # Run PHP tests
npm run test                   # Run JS tests

# Deployment
./deploy.sh                    # Full deployment
./deploy.sh quick              # Quick deployment
```

---

## 📁 ไฟล์สำคัญ

| ไฟล์ | คำอธิบาย |
|------|----------|
| `resources/js/data/provinces.js` | ข้อมูล 77 จังหวัด + SVG paths |
| `resources/js/data/constituencies.js` | ข้อมูล 400 เขตเลือกตั้ง |
| `resources/js/data/parties.js` | ข้อมูลพรรคการเมือง + สี |
| `app/Services/ResultScraperService.php` | Multi-source data scraping |
| `app/Services/NewsAggregatorService.php` | AI News aggregation |
| `resources/js/components/map/ThailandMap.vue` | แผนที่ประเทศไทย |

---

## ⚠️ ข้อควรระวัง

### DO's ✅
- ใช้ Service classes สำหรับ business logic
- ใช้ Form Requests สำหรับ validation
- ใช้ Resources สำหรับ API responses
- ใช้ Events สำหรับ side effects
- เขียน tests สำหรับ critical features
- ใช้ typed properties และ return types

### DON'Ts ❌
- อย่าใส่ business logic ใน Controllers
- อย่า hard-code ค่าที่ควรเป็น config
- อย่าใช้ raw queries ถ้าไม่จำเป็น
- อย่า commit secrets หรือ credentials
- อย่าลืม handle errors อย่างเหมาะสม
- อย่าใช้ `any` type ใน TypeScript/JSDoc

---

## 🔐 Security

```php
// ใช้ Policies สำหรับ authorization
$this->authorize('update', $election);

// ใช้ Sanctum สำหรับ API auth
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});

// Validate ทุก input
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
]);

// ใช้ CSRF protection
@csrf // ใน Blade forms
```

---

## 📊 Data Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Sources   │────▶│   Scraper   │────▶│  Database   │
│  (กกต, News)│     │   Service   │     │   (MySQL)   │
└─────────────┘     └─────────────┘     └──────┬──────┘
                                               │
                                               ▼
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Browser   │◀────│  WebSocket  │◀────│   Events    │
│  (Vue.js)   │     │  (Reverb)   │     │ (Broadcast) │
└─────────────┘     └─────────────┘     └─────────────┘
```

---

## 🚀 การ Deploy

1. ใช้ `./deploy.sh` สำหรับ production deployment
2. ตรวจสอบ `.env` ก่อน deploy
3. Run migrations ด้วย `--force` flag
4. Clear และ cache config/routes
5. Restart queue workers และ WebSocket

---

## 📝 Git Workflow

```bash
# Feature branch
git checkout -b feature/add-new-chart
git commit -m "feat: Add vote distribution chart"
git push origin feature/add-new-chart

# Commit message format
feat: Add new feature
fix: Fix bug
docs: Update documentation
style: Code style changes
refactor: Refactoring
test: Add tests
chore: Maintenance
```

---

## 🤖 สำหรับ Claude AI

เมื่อทำงานกับโปรเจคนี้:

1. **อ่านไฟล์นี้ก่อนเสมอ** เพื่อเข้าใจ conventions
2. **ใช้ Service pattern** สำหรับ business logic ใหม่
3. **ตาม naming conventions** ที่กำหนดไว้
4. **เขียน migrations** ด้วย foreign keys และ indexes
5. **ใช้ typed code** (PHP type hints, JSDoc)
6. **อัปเดต docs** เมื่อเพิ่ม features ใหม่

---

*Last updated: 2024*
