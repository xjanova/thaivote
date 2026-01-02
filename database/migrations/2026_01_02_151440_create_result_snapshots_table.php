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
        Schema::create('result_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->timestamp('snapshot_at');
            $table->decimal('counting_progress', 5, 2)->default(0);
            $table->unsignedInteger('constituencies_counted')->default(0);
            $table->unsignedInteger('stations_counted')->default(0);
            $table->unsignedBigInteger('total_votes_cast')->default(0);
            $table->json('party_results');
            $table->json('leading_parties')->nullable();
            $table->timestamps();

            $table->index(['election_id', 'snapshot_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_snapshots');
    }
};
