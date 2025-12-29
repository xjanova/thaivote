<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name_th');
            $table->string('name_en');
            $table->string('code', 10)->unique();
            $table->string('region'); // north, northeast, central, east, west, south
            $table->integer('population')->default(0);
            $table->integer('total_districts')->default(0);
            $table->integer('total_constituencies')->default(0);
            $table->json('geo_data')->nullable(); // GeoJSON for map
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->string('name_th');
            $table->string('name_en');
            $table->string('code', 20)->unique();
            $table->integer('population')->default(0);
            $table->json('geo_data')->nullable();
            $table->timestamps();
        });

        Schema::create('constituencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->integer('number');
            $table->string('name')->nullable();
            $table->integer('total_eligible_voters')->default(0);
            $table->integer('total_polling_stations')->default(0);
            $table->json('geo_data')->nullable();
            $table->json('district_ids')->nullable(); // districts in this constituency
            $table->timestamps();

            $table->unique(['province_id', 'number']);
        });

        Schema::create('polling_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('constituency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('station_number');
            $table->string('name');
            $table->string('location')->nullable();
            $table->integer('eligible_voters')->default(0);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_stations');
        Schema::dropIfExists('constituencies');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
    }
};
