# Contributing to ThaiVote

ขอบคุณที่สนใจมีส่วนร่วมพัฒนา ThaiVote! 🎉

## 📋 สารบัญ

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)

---

## Code of Conduct

- เคารพความคิดเห็นของผู้อื่น
- ใช้ภาษาสุภาพและสร้างสรรค์
- ช่วยเหลือผู้พัฒนาคนอื่นเมื่อมีโอกาส
- รับฟัง feedback และพร้อมปรับปรุง

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+
- Git

### Setup Development Environment

```bash
# 1. Clone repository
git clone https://github.com/xjanova/thaivote.git
cd thaivote

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaivote
DB_USERNAME=root
DB_PASSWORD=

# 5. Run migrations
php artisan migrate --seed

# 6. Build assets
npm run build

# 7. Start development servers
php artisan serve        # Terminal 1
npm run dev              # Terminal 2
php artisan reverb:start # Terminal 3 (WebSocket)
```

---

## Development Workflow

### 1. Create Feature Branch

```bash
# ดึง code ล่าสุด
git checkout main
git pull origin main

# สร้าง branch ใหม่
git checkout -b feature/your-feature-name
```

### 2. Branch Naming Convention

```
feature/    - ฟีเจอร์ใหม่           (feature/add-chart)
fix/        - แก้ bug               (fix/map-zoom-issue)
docs/       - อัปเดต documentation   (docs/api-endpoints)
refactor/   - refactoring           (refactor/service-layer)
test/       - เพิ่ม tests           (test/election-service)
chore/      - maintenance           (chore/update-deps)
```

### 3. Commit Message Format

```
<type>: <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: ฟีเจอร์ใหม่
- `fix`: แก้ bug
- `docs`: documentation
- `style`: formatting, semicolons, etc.
- `refactor`: code refactoring
- `test`: tests
- `chore`: maintenance

**Examples:**
```bash
git commit -m "feat: Add province detail modal"
git commit -m "fix: Correct vote calculation for Bangkok"
git commit -m "docs: Update API documentation"
```

### 4. Keep Branch Updated

```bash
git fetch origin
git rebase origin/main
```

---

## Pull Request Process

### 1. Before Creating PR

- [ ] Code follows project conventions (see CLAUDE.md)
- [ ] All tests pass (`php artisan test`)
- [ ] Code is properly formatted (`./vendor/bin/pint`)
- [ ] No console errors in browser
- [ ] Self-review completed
- [ ] Documentation updated (if needed)

### 2. PR Title Format

```
[TYPE] Brief description

Examples:
[FEAT] Add vote distribution chart
[FIX] Correct province boundary display
[DOCS] Update installation guide
```

### 3. PR Description Template

```markdown
## Summary
Brief description of changes

## Changes
- Change 1
- Change 2

## Screenshots (if UI changes)
![Screenshot](url)

## Testing
- [ ] Unit tests added/updated
- [ ] Manual testing completed

## Related Issues
Closes #123
```

### 4. Review Process

1. Automated checks must pass
2. At least 1 approval required
3. No unresolved conversations
4. Squash and merge preferred

---

## Coding Standards

### PHP (Laravel)

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Election;
use Illuminate\Support\Collection;

final class ElectionService
{
    public function __construct(
        private readonly ResultService $resultService,
    ) {}

    /**
     * Get active elections with results.
     *
     * @return Collection<int, Election>
     */
    public function getActiveElections(): Collection
    {
        return Election::query()
            ->where('status', 'active')
            ->with(['results', 'provinces'])
            ->get();
    }
}
```

**Key Rules:**
- Use `declare(strict_types=1)`
- Use typed properties and return types
- Use constructor property promotion
- Use `final` for classes not meant to be extended
- Document with PHPDoc for complex methods

### JavaScript/Vue

```javascript
// Use Composition API
<script setup>
import { ref, computed, onMounted } from 'vue'

// Props with types
const props = defineProps({
    electionId: {
        type: Number,
        required: true
    }
})

// Emits
const emit = defineEmits(['update', 'close'])

// Reactive state
const isLoading = ref(false)
const results = ref([])

// Computed
const totalVotes = computed(() =>
    results.value.reduce((sum, r) => sum + r.votes, 0)
)

// Methods
async function fetchData() {
    isLoading.value = true
    try {
        // ...
    } finally {
        isLoading.value = false
    }
}

// Lifecycle
onMounted(() => {
    fetchData()
})
</script>
```

**Key Rules:**
- Use Composition API with `<script setup>`
- Use TypeScript or JSDoc for type hints
- Handle loading and error states
- Use async/await for promises

### CSS/Tailwind

```vue
<template>
    <!-- Use Tailwind classes -->
    <div class="flex items-center gap-4 p-4 bg-white rounded-lg shadow">
        <h1 class="text-xl font-bold text-gray-900">Title</h1>
    </div>
</template>

<style scoped>
/* Use scoped styles for component-specific CSS */
.custom-component {
    /* Use CSS variables for theming */
    --primary-color: theme('colors.blue.600');
}
</style>
```

---

## Testing

### PHP Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter ElectionServiceTest

# Run with coverage
php artisan test --coverage
```

**Test Structure:**
```php
<?php

namespace Tests\Unit\Services;

use App\Services\ElectionService;
use Tests\TestCase;

class ElectionServiceTest extends TestCase
{
    private ElectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ElectionService::class);
    }

    public function test_can_get_active_elections(): void
    {
        // Arrange
        Election::factory()->active()->count(3)->create();

        // Act
        $result = $this->service->getActiveElections();

        // Assert
        $this->assertCount(3, $result);
    }
}
```

### JavaScript Tests

```bash
# Run tests
npm run test

# Run with watch
npm run test:watch
```

---

## Documentation

### When to Update Docs

- Adding new API endpoints → Update `docs/API.md`
- Changing architecture → Update `docs/ARCHITECTURE.md`
- Adding new features → Update README.md
- Changing conventions → Update CLAUDE.md

### Documentation Files

| File | Purpose |
|------|---------|
| `README.md` | Project overview, quick start |
| `CLAUDE.md` | Development rules for AI/developers |
| `CONTRIBUTING.md` | Contribution guidelines (this file) |
| `docs/ARCHITECTURE.md` | System architecture |
| `docs/API.md` | API documentation |
| `docs/DEVELOPMENT.md` | Development guide |

---

## Questions?

- Open an issue for bugs or feature requests
- Join discussions for questions
- Contact maintainers for sensitive issues

---

ขอบคุณที่ช่วยพัฒนา ThaiVote! 🙏
