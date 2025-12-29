# ThaiVote Development Guide

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+
- Redis (optional, for caching/queues)

### Installation

```bash
# Clone repository
git clone https://github.com/your-org/thaivote.git
cd thaivote

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaivote
DB_USERNAME=root
DB_PASSWORD=

# Run migrations and seeders
php artisan migrate --seed

# Build frontend assets
npm run dev

# Start development server
php artisan serve
```

---

## Development Environment

### VS Code Extensions (Recommended)

```json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "shufo.vscode-blade-formatter",
        "vue.volar",
        "bradlc.vscode-tailwindcss",
        "dbaeumer.vscode-eslint",
        "esbenp.prettier-vscode",
        "EditorConfig.EditorConfig"
    ]
}
```

### Docker Development

```bash
# Start all services
docker-compose up -d

# Access application
open http://localhost:8000

# Run artisan commands
docker-compose exec app php artisan migrate

# Access MySQL
docker-compose exec db mysql -u root -p
```

**docker-compose.yml:**
```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: thaivote
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"

  reverb:
    build: .
    command: php artisan reverb:start
    depends_on:
      - app

volumes:
  mysql_data:
```

---

## Project Structure

```
thaivote/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/           # API controllers
│   │   │   ├── Admin/         # Admin panel controllers
│   │   │   └── Install/       # Installation wizard
│   │   ├── Middleware/
│   │   └── Resources/         # API resources
│   ├── Models/                # Eloquent models
│   ├── Services/              # Business logic
│   ├── Events/                # Broadcasting events
│   ├── Jobs/                  # Queue jobs
│   ├── Policies/              # Authorization policies
│   └── Providers/
│
├── resources/
│   ├── js/
│   │   ├── app.js            # Vue entry point
│   │   ├── components/       # Vue components
│   │   │   ├── map/         # Map components
│   │   │   ├── admin/       # Admin components
│   │   │   └── common/      # Shared components
│   │   ├── pages/           # Inertia pages
│   │   ├── stores/          # Pinia stores
│   │   ├── composables/     # Vue composables
│   │   └── data/            # Static data (provinces, parties)
│   ├── views/               # Blade templates
│   └── css/
│
├── routes/
│   ├── web.php              # Web routes
│   ├── api.php              # API routes
│   └── channels.php         # Broadcast channels
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── docs/                    # Documentation
├── scripts/                 # Deployment scripts
└── config/                  # Laravel config
```

---

## Development Workflow

### Feature Development

```bash
# 1. Create feature branch
git checkout -b feature/your-feature-name

# 2. Make changes
# ...

# 3. Run tests
php artisan test
npm run test

# 4. Check code style
./vendor/bin/pint
npm run lint

# 5. Commit
git add .
git commit -m "feat: add your feature description"

# 6. Push and create PR
git push -u origin feature/your-feature-name
```

### Database Changes

```bash
# Create migration
php artisan make:migration create_table_name

# Create model with migration
php artisan make:model ModelName -m

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh database with seeders
php artisan migrate:fresh --seed
```

### Creating Components

```bash
# Create controller
php artisan make:controller Api/ResourceController --api

# Create service
php artisan make:class Services/ResourceService

# Create event
php artisan make:event ResourceUpdated

# Create job
php artisan make:job ProcessResource

# Create policy
php artisan make:policy ResourcePolicy --model=Resource
```

---

## Testing

### Running Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/ElectionTest.php

# With coverage
php artisan test --coverage

# Parallel testing
php artisan test --parallel

# Frontend tests
npm run test
npm run test:coverage
```

### Writing Tests

**Feature Test Example:**
```php
<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_elections(): void
    {
        Election::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/elections');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_election(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/elections', [
                'name' => 'Test Election',
                'election_date' => '2024-01-01',
                'type' => 'general',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('elections', ['name' => 'Test Election']);
    }
}
```

**Vue Component Test:**
```javascript
import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import ThailandMap from '@/components/map/ThailandMap.vue';

describe('ThailandMap', () => {
    it('renders all provinces', () => {
        const wrapper = mount(ThailandMap, {
            props: {
                electionId: 1,
            },
        });

        expect(wrapper.findAll('.province')).toHaveLength(77);
    });

    it('emits province-click on province selection', async () => {
        const wrapper = mount(ThailandMap);

        await wrapper.find('.province[data-code="BKK"]').trigger('click');

        expect(wrapper.emitted('province-click')).toBeTruthy();
    });
});
```

---

## Code Style

### PHP (Laravel Pint)

```bash
# Check style
./vendor/bin/pint --test

# Fix style
./vendor/bin/pint
```

**pint.json:**
```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": true,
        "blank_line_before_statement": {
            "statements": ["return"]
        }
    }
}
```

### JavaScript (ESLint + Prettier)

```bash
# Check style
npm run lint

# Fix style
npm run lint:fix

# Format with Prettier
npm run format
```

**.eslintrc.cjs:**
```javascript
module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
        'prettier',
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    rules: {
        'vue/multi-word-component-names': 'off',
        'vue/require-default-prop': 'off',
    },
};
```

---

## Database

### Seeding

```bash
# Run all seeders
php artisan db:seed

# Specific seeder
php artisan db:seed --class=ElectionSeeder

# Fresh with seed
php artisan migrate:fresh --seed
```

**Creating Seeder:**
```php
<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['code' => 'BKK', 'name_th' => 'กรุงเทพมหานคร', 'name_en' => 'Bangkok', 'region' => 'central'],
            // ... more provinces
        ];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']],
                $province
            );
        }
    }
}
```

### Factories

```php
<?php

namespace Database\Factories;

use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElectionFactory extends Factory
{
    protected $model = Election::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['general', 'senate', 'local']),
            'election_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => 'upcoming',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'election_date' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'election_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
```

---

## Real-time Features

### Broadcasting Setup

```bash
# Start Reverb server
php artisan reverb:start

# In development
php artisan reverb:start --debug
```

### Creating Events

```php
<?php

namespace App\Events;

use App\Models\Election;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ResultsUpdated implements ShouldBroadcast
{
    public function __construct(
        public Election $election,
        public array $results
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('election.' . $this->election->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'election_id' => $this->election->id,
            'results' => $this->results,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

### Frontend Listening

```javascript
import { onMounted, onUnmounted } from 'vue';

export function useElectionUpdates(electionId, callback) {
    onMounted(() => {
        window.Echo.channel(`election.${electionId}`)
            .listen('ResultsUpdated', callback);
    });

    onUnmounted(() => {
        window.Echo.leave(`election.${electionId}`);
    });
}
```

---

## Queue Jobs

### Running Workers

```bash
# Start worker
php artisan queue:work

# With specific queue
php artisan queue:work --queue=scraping,default

# For production (with supervisor)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### Creating Jobs

```php
<?php

namespace App\Jobs;

use App\Services\ResultScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int $electionId,
        public string $source
    ) {}

    public function handle(ResultScraperService $scraper): void
    {
        $scraper->scrapeFromSource($this->electionId, $this->source);
    }
}
```

### Dispatching Jobs

```php
// Immediate
ScrapeResultsJob::dispatch($electionId, 'ect');

// Delayed
ScrapeResultsJob::dispatch($electionId, 'ect')
    ->delay(now()->addMinutes(5));

// On specific queue
ScrapeResultsJob::dispatch($electionId, 'ect')
    ->onQueue('scraping');
```

---

## Caching

### Basic Usage

```php
use Illuminate\Support\Facades\Cache;

// Store
Cache::put('key', $value, now()->addMinutes(60));

// Retrieve
$value = Cache::get('key');

// Remember pattern
$results = Cache::remember('election.1.results', 300, function () {
    return Election::find(1)->results;
});

// Tags (Redis only)
Cache::tags(['elections', 'results'])->put('key', $value, 300);
Cache::tags(['elections'])->flush();
```

### Cache Invalidation

```php
// In ElectionObserver
public function updated(Election $election): void
{
    Cache::forget("election.{$election->id}");
    Cache::tags(['elections'])->flush();
}
```

---

## Debugging

### Laravel Telescope

```bash
# Install
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access at /telescope
```

### Debug Bar

```bash
# Install
composer require barryvdh/laravel-debugbar --dev

# Disable in production (.env)
DEBUGBAR_ENABLED=false
```

### Vue DevTools

Install browser extension for Vue.js debugging.

### Logging

```php
use Illuminate\Support\Facades\Log;

Log::info('Message', ['context' => $data]);
Log::error('Error occurred', ['exception' => $e->getMessage()]);

// Custom channel
Log::channel('scraper')->info('Scraping started');
```

---

## Performance Tips

### Database

```php
// Eager loading
$elections = Election::with(['provinces', 'parties'])->get();

// Chunking large datasets
Election::chunk(100, function ($elections) {
    foreach ($elections as $election) {
        // Process
    }
});

// Select only needed columns
Province::select(['id', 'name_th', 'code'])->get();
```

### Frontend

```javascript
// Lazy load components
const ThailandMap = defineAsyncComponent(() =>
    import('./components/map/ThailandMap.vue')
);

// Virtual scrolling for large lists
import { RecycleScroller } from 'vue-virtual-scroller';
```

### Caching

```php
// Query caching
$provinces = Cache::remember('provinces.all', 3600, function () {
    return Province::with('constituencies')->get();
});

// Response caching
return response()->json($data)
    ->header('Cache-Control', 'public, max-age=300');
```

---

## Common Commands

```bash
# Clear all caches
php artisan optimize:clear

# Generate IDE helpers
php artisan ide-helper:generate
php artisan ide-helper:models

# Route list
php artisan route:list

# Queue monitoring
php artisan queue:monitor

# Maintenance mode
php artisan down --secret="your-secret"
php artisan up

# Build for production
npm run build
php artisan optimize
```

---

## Troubleshooting

### Common Issues

**1. Class not found after adding new model:**
```bash
composer dump-autoload
```

**2. Cache issues:**
```bash
php artisan optimize:clear
```

**3. Permission issues:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**4. WebSocket not connecting:**
- Check Reverb is running: `php artisan reverb:start`
- Verify `.env` settings: `REVERB_APP_KEY`, `REVERB_HOST`, `REVERB_PORT`

**5. Queue jobs not processing:**
- Check worker is running: `php artisan queue:work`
- Check failed jobs: `php artisan queue:failed`
- Retry failed: `php artisan queue:retry all`

---

*For API documentation, see [API.md](./API.md)*
*For architecture overview, see [ARCHITECTURE.md](./ARCHITECTURE.md)*
