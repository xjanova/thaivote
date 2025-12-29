<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Election result data sources (for scraping multiple news sites)
        Schema::create('result_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website');
            $table->string('logo')->nullable();
            $table->enum('type', ['official', 'news', 'api'])->default('news');
            $table->json('scrape_config')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->string('api_key')->nullable();
            $table->integer('reliability_score')->default(50); // 0-100
            $table->integer('priority')->default(50);
            $table->integer('fetch_interval')->default(60); // seconds
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->json('last_fetch_stats')->nullable();
            $table->timestamps();
        });

        // Raw scraped data before processing
        Schema::create('scraped_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('scope'); // national, province, constituency, station
            $table->string('scope_id')->nullable();
            $table->json('raw_data');
            $table->json('parsed_data')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_valid')->default(true);
            $table->string('error_message')->nullable();
            $table->timestamp('scraped_at');
            $table->timestamps();

            $table->index(['election_id', 'scope', 'is_processed']);
        });

        // Source comparison & accuracy tracking
        Schema::create('source_accuracy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->integer('total_data_points')->default(0);
            $table->integer('accurate_data_points')->default(0);
            $table->decimal('accuracy_percentage', 5, 2)->default(0);
            $table->integer('avg_delay_seconds')->default(0); // How fast vs official
            $table->json('discrepancies')->nullable();
            $table->timestamps();

            $table->unique(['result_source_id', 'election_id']);
        });

        // Consensus results (aggregated from multiple sources)
        Schema::create('consensus_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('scope'); // national, province, constituency
            $table->unsignedBigInteger('scope_id');
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('votes')->default(0);
            $table->decimal('confidence_score', 5, 2)->default(0); // Based on source agreement
            $table->integer('sources_count')->default(0);
            $table->json('source_values')->nullable(); // Value from each source
            $table->boolean('has_discrepancy')->default(false);
            $table->timestamps();

            $table->index(['election_id', 'scope', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consensus_results');
        Schema::dropIfExists('source_accuracy');
        Schema::dropIfExists('scraped_results');
        Schema::dropIfExists('result_sources');
    }
};
