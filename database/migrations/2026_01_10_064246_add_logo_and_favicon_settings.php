<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert logo and favicon settings
        DB::table('settings')->insert([
            [
                'key' => 'site_logo',
                'value' => '',
                'type' => 'string',
                'group' => 'appearance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_favicon',
                'value' => '',
                'type' => 'string',
                'group' => 'appearance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['site_logo', 'site_favicon'])->delete();
    }
};
