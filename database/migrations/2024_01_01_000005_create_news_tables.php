<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // News sources configuration
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_th')->nullable();
            $table->string('website');
            $table->string('logo')->nullable();
            $table->string('rss_url')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->json('scrape_config')->nullable(); // CSS selectors, etc.
            $table->enum('type', ['rss', 'api', 'scrape'])->default('rss');
            $table->integer('priority')->default(50);
            $table->integer('fetch_interval')->default(300); // seconds
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        // Keywords to track
        Schema::create('news_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('category')->nullable(); // party, candidate, election, etc.
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('election_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('priority')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Collected news articles
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('url')->unique();
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('keywords_matched')->nullable();
            $table->decimal('relevance_score', 5, 2)->default(0);
            $table->string('sentiment')->nullable(); // positive, negative, neutral
            $table->decimal('sentiment_score', 5, 2)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();

            $table->index('published_at');
            $table->index('relevance_score');
            $table->fullText(['title', 'content']);
        });

        // News article - party relationship
        Schema::create('news_article_party', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['news_article_id', 'party_id']);
        });

        // Party page feeds (Facebook, etc.)
        Schema::create('party_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // facebook, twitter, instagram, youtube
            $table->string('page_id');
            $table->string('page_url');
            $table->string('access_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // Party feed posts
        Schema::create('party_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_feed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('platform_post_id');
            $table->text('content');
            $table->string('post_url');
            $table->string('media_type')->nullable();
            $table->json('media_urls')->nullable();
            $table->integer('likes')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('comments')->default(0);
            $table->timestamp('posted_at');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->unique(['party_feed_id', 'platform_post_id']);
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_posts');
        Schema::dropIfExists('party_feeds');
        Schema::dropIfExists('news_article_party');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('news_keywords');
        Schema::dropIfExists('news_sources');
    }
};
