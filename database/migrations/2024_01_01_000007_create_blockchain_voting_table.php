<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Blockchain configuration
        Schema::create('blockchain_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('network'); // ethereum, polygon, etc.
            $table->string('contract_address')->nullable();
            $table->string('contract_abi')->nullable();
            $table->string('rpc_endpoint');
            $table->string('chain_id');
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Voter registration for blockchain voting
        Schema::create('blockchain_voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('wallet_address')->nullable();
            $table->string('voter_id_hash'); // Hashed citizen ID
            $table->string('eligibility_proof')->nullable(); // ZK proof
            $table->boolean('is_verified')->default(false);
            $table->boolean('has_voted')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'voter_id_hash']);
            $table->unique(['election_id', 'wallet_address']);
        });

        // Blockchain votes (encrypted)
        Schema::create('blockchain_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_hash')->unique();
            $table->string('block_number')->nullable();
            $table->string('vote_commitment'); // Encrypted vote
            $table->string('nullifier')->unique(); // Prevents double voting
            $table->timestamp('submitted_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index(['election_id', 'status']);
        });

        // Vote tallying (after election ends)
        Schema::create('blockchain_tallies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('votes')->default(0);
            $table->string('merkle_root')->nullable();
            $table->string('proof')->nullable(); // ZK proof of correct tallying
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // Audit log for blockchain operations
        Schema::create('blockchain_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // vote_submitted, vote_confirmed, tally_started, etc.
            $table->string('transaction_hash')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['election_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_audit_logs');
        Schema::dropIfExists('blockchain_tallies');
        Schema::dropIfExists('blockchain_votes');
        Schema::dropIfExists('blockchain_voters');
        Schema::dropIfExists('blockchain_configs');
    }
};
