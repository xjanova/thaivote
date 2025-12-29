<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('name_th');
            $table->string('name_en');
            $table->string('abbreviation', 20);
            $table->string('logo')->nullable();
            $table->string('color', 7)->default('#666666'); // HEX color
            $table->string('secondary_color', 7)->nullable();
            $table->text('description')->nullable();
            $table->string('slogan')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook_page')->nullable();
            $table->string('twitter_handle')->nullable();
            $table->string('leader_name')->nullable();
            $table->string('leader_photo')->nullable();
            $table->year('founded_year')->nullable();
            $table->string('headquarters')->nullable();
            $table->integer('party_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('api_key')->nullable()->unique(); // For party API access
            $table->string('api_secret')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('constituency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('candidate_number', 10);
            $table->string('title', 50)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('nickname')->nullable();
            $table->string('photo')->nullable();
            $table->text('biography')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->enum('type', ['constituency', 'party_list'])->default('constituency');
            $table->integer('party_list_order')->nullable();
            $table->boolean('is_pm_candidate')->default(false);
            $table->json('social_media')->nullable();
            $table->json('policies')->nullable();
            $table->timestamps();

            $table->index(['election_id', 'constituency_id']);
            $table->index(['election_id', 'party_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('parties');
    }
};
