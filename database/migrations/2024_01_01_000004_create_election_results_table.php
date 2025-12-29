<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Results per polling station
        Schema::create('station_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('polling_station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->integer('votes')->default(0);
            $table->boolean('is_official')->default(false);
            $table->timestamp('counted_at')->nullable();
            $table->string('source')->default('manual'); // manual, scraper, api
            $table->string('source_url')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'polling_station_id', 'candidate_id'], 'station_result_unique');
            $table->index(['election_id', 'candidate_id']);
        });

        // Aggregated results per constituency
        Schema::create('constituency_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('constituency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('votes')->default(0);
            $table->decimal('vote_percentage', 5, 2)->default(0);
            $table->integer('rank')->default(0);
            $table->boolean('is_winner')->default(false);
            $table->integer('stations_counted')->default(0);
            $table->integer('stations_total')->default(0);
            $table->decimal('counting_progress', 5, 2)->default(0);
            $table->boolean('is_official')->default(false);
            $table->timestamps();

            $table->unique(['election_id', 'constituency_id', 'candidate_id'], 'const_result_unique');
            $table->index(['election_id', 'party_id']);
        });

        // Province level aggregation
        Schema::create('province_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->integer('total_votes')->default(0);
            $table->integer('seats_won')->default(0);
            $table->decimal('vote_percentage', 5, 2)->default(0);
            $table->integer('constituencies_counted')->default(0);
            $table->integer('constituencies_total')->default(0);
            $table->timestamps();

            $table->unique(['election_id', 'province_id', 'party_id']);
        });

        // National level aggregation
        Schema::create('national_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->integer('constituency_votes')->default(0);
            $table->integer('party_list_votes')->default(0);
            $table->integer('total_votes')->default(0);
            $table->integer('constituency_seats')->default(0);
            $table->integer('party_list_seats')->default(0);
            $table->integer('total_seats')->default(0);
            $table->decimal('vote_percentage', 5, 2)->default(0);
            $table->integer('rank')->default(0);
            $table->timestamps();

            $table->unique(['election_id', 'party_id']);
        });

        // Summary stats
        Schema::create('election_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->integer('total_eligible_voters')->default(0);
            $table->integer('total_votes_cast')->default(0);
            $table->integer('valid_votes')->default(0);
            $table->integer('invalid_votes')->default(0);
            $table->integer('no_vote')->default(0);
            $table->decimal('voter_turnout', 5, 2)->default(0);
            $table->integer('constituencies_counted')->default(0);
            $table->integer('constituencies_total')->default(0);
            $table->integer('stations_counted')->default(0);
            $table->integer('stations_total')->default(0);
            $table->decimal('counting_progress', 5, 2)->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique('election_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_stats');
        Schema::dropIfExists('national_results');
        Schema::dropIfExists('province_results');
        Schema::dropIfExists('constituency_results');
        Schema::dropIfExists('station_results');
    }
};
