<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * ข้อมูลอ้างอิงจาก กกต. (สำนักงานคณะกรรมการการเลือกตั้ง)
     * https://www.ect.go.th/
     */
    public function run(): void
    {
        // Seed พรรคการเมือง, จังหวัด, และเขตเลือกตั้ง (ตามข้อมูล กกต.)
        $this->call([
            ProvinceSeeder::class,      // 77 จังหวัด
            ConstituencySeeder::class,  // 400 เขตเลือกตั้ง
            PartySeeder::class,         // พรรคการเมืองหลัก
        ]);

        // Create test user only if it doesn't exist
        if (! User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            $this->command->info('Test user created: test@example.com');
        } else {
            $this->command->info('Test user already exists, skipping.');
        }
    }
}
