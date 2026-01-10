<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general'); // general, appearance, notifications, etc.
            $table->timestamps();

            $table->index('group');
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'site_name', 'value' => 'ThaiVote', 'type' => 'string', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_description', 'value' => 'ระบบรายงานผลเลือกตั้งแบบเรียลไทม์', 'type' => 'string', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_refresh_interval', 'value' => '60', 'type' => 'integer', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'news_fetch_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'news', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'news_fetch_interval', 'value' => '300', 'type' => 'integer', 'group' => 'news', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'results_scrape_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'results', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'results_scrape_interval', 'value' => '60', 'type' => 'integer', 'group' => 'results', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
