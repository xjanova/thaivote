<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PDO;
use PDOException;

class InstallController extends Controller
{
    /**
     * Wizard steps
     */
    protected array $steps = [
        1 => 'welcome',
        2 => 'requirements',
        3 => 'database',
        4 => 'application',
        5 => 'admin',
        6 => 'complete',
    ];

    /**
     * Required PHP extensions
     */
    protected array $requirements = [
        'php_version' => '8.2.0',
        'extensions' => [
            'pdo',
            'pdo_mysql',
            'mbstring',
            'openssl',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'bcmath',
            'curl',
            'fileinfo',
        ],
        'directories' => [
            'storage/app',
            'storage/framework',
            'storage/logs',
            'bootstrap/cache',
        ],
    ];

    /**
     * Check if installation is required
     */
    public function checkInstallation()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return redirect()->route('install.welcome');
    }

    /**
     * Step 1: Welcome page
     */
    public function welcome()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.welcome', [
            'step' => 1,
            'totalSteps' => count($this->steps),
        ]);
    }

    /**
     * Step 2: Requirements check
     */
    public function requirements()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $checks = $this->checkRequirements();

        return view('install.requirements', [
            'step' => 2,
            'totalSteps' => count($this->steps),
            'checks' => $checks,
            'allPassed' => $this->allRequirementsPassed($checks),
        ]);
    }

    /**
     * Step 3: Database configuration form
     */
    public function database()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.database', [
            'step' => 3,
            'totalSteps' => count($this->steps),
            'config' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'thaivote'),
                'username' => env('DB_USERNAME', 'root'),
            ],
        ]);
    }

    /**
     * Step 3: Process database configuration
     */
    public function databaseStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Test database connection
        try {
            $dsn = "mysql:host={$request->db_host};port={$request->db_port}";
            $pdo = new PDO($dsn, $request->db_username, $request->db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$request->db_database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        } catch (PDOException $e) {
            return back()->withErrors([
                'db_connection' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage()
            ])->withInput();
        }

        // Update .env file
        $this->updateEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ?? '',
        ]);

        // Clear config cache
        Artisan::call('config:clear');

        // Store in session for later use
        session(['install.database' => true]);

        return redirect()->route('install.application');
    }

    /**
     * Step 4: Application configuration form
     */
    public function application()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.application', [
            'step' => 4,
            'totalSteps' => count($this->steps),
            'config' => [
                'app_name' => env('APP_NAME', 'ThaiVote'),
                'app_url' => env('APP_URL', 'http://localhost'),
                'app_env' => env('APP_ENV', 'local'),
            ],
        ]);
    }

    /**
     * Step 4: Process application configuration
     */
    public function applicationStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_env' => 'required|in:local,production',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Update .env file
        $this->updateEnv([
            'APP_NAME' => $request->app_name,
            'APP_URL' => $request->app_url,
            'APP_ENV' => $request->app_env,
            'APP_DEBUG' => $request->app_env === 'local' ? 'true' : 'false',
        ]);

        // Generate app key if not exists
        if (empty(env('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'migration' => 'เกิดข้อผิดพลาดในการสร้างตาราง: ' . $e->getMessage()
            ])->withInput();
        }

        // Create storage link
        Artisan::call('storage:link');

        session(['install.application' => true]);

        return redirect()->route('install.admin');
    }

    /**
     * Step 5: Admin user form
     */
    public function admin()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return view('install.admin', [
            'step' => 5,
            'totalSteps' => count($this->steps),
        ]);
    }

    /**
     * Step 5: Create admin user
     */
    public function adminStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Create admin user
            DB::table('users')->insert([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_admin' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // User might already exist or table structure different
            // Try alternative approach
            try {
                $userClass = config('auth.providers.users.model', 'App\Models\User');
                $user = new $userClass();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = Hash::make($request->password);
                $user->email_verified_at = now();
                if (in_array('is_admin', $user->getFillable())) {
                    $user->is_admin = true;
                }
                $user->save();
            } catch (\Exception $e2) {
                return back()->withErrors([
                    'user' => 'ไม่สามารถสร้างผู้ใช้งานได้: ' . $e2->getMessage()
                ])->withInput();
            }
        }

        session(['install.admin' => true]);

        return redirect()->route('install.complete');
    }

    /**
     * Step 6: Installation complete
     */
    public function complete()
    {
        // Mark as installed
        $this->markAsInstalled();

        // Clear all caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        // Remove installing flag
        if (File::exists(storage_path('app/installing'))) {
            File::delete(storage_path('app/installing'));
        }

        return view('install.complete', [
            'step' => 6,
            'totalSteps' => count($this->steps),
            'appUrl' => env('APP_URL', url('/')),
        ]);
    }

    /**
     * Check if application is already installed
     */
    protected function isInstalled(): bool
    {
        return File::exists(storage_path('app/installed'));
    }

    /**
     * Mark application as installed
     */
    protected function markAsInstalled(): void
    {
        $content = "installed_at=" . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "installed_by=wizard\n";
        $content .= "php_version=" . PHP_VERSION . "\n";

        File::put(storage_path('app/installed'), $content);
    }

    /**
     * Check all requirements
     */
    protected function checkRequirements(): array
    {
        $checks = [];

        // PHP Version
        $checks['php_version'] = [
            'name' => 'PHP Version >= ' . $this->requirements['php_version'],
            'current' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, $this->requirements['php_version'], '>='),
        ];

        // PHP Extensions
        foreach ($this->requirements['extensions'] as $extension) {
            $checks['ext_' . $extension] = [
                'name' => 'PHP Extension: ' . $extension,
                'current' => extension_loaded($extension) ? 'Installed' : 'Not installed',
                'passed' => extension_loaded($extension),
            ];
        }

        // Directory Permissions
        foreach ($this->requirements['directories'] as $directory) {
            $path = base_path($directory);
            $writable = is_writable($path);
            $checks['dir_' . str_replace('/', '_', $directory)] = [
                'name' => 'Directory Writable: ' . $directory,
                'current' => $writable ? 'Writable' : 'Not writable',
                'passed' => $writable,
            ];
        }

        // .env file
        $envWritable = is_writable(base_path('.env'));
        $checks['env_file'] = [
            'name' => '.env File Writable',
            'current' => $envWritable ? 'Writable' : 'Not writable',
            'passed' => $envWritable,
        ];

        return $checks;
    }

    /**
     * Check if all requirements passed
     */
    protected function allRequirementsPassed(array $checks): bool
    {
        foreach ($checks as $check) {
            if (!$check['passed']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Update .env file
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($values as $key => $value) {
            // Escape special characters
            if (preg_match('/\s/', $value) || str_contains($value, '#')) {
                $value = '"' . $value . '"';
            }

            // Check if key exists
            if (preg_match("/^{$key}=/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);
    }
}
