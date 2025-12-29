<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('type'); // general, local, referendum
            $table->text('description')->nullable();
            $table->date('election_date');
            $table->time('start_time')->default('08:00:00');
            $table->time('end_time')->default('17:00:00');
            $table->enum('status', ['upcoming', 'ongoing', 'counting', 'completed'])->default('upcoming');
            $table->integer('total_eligible_voters')->default(0);
            $table->integer('total_votes_cast')->default(0);
            $table->decimal('voter_turnout', 5, 2)->default(0);
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
