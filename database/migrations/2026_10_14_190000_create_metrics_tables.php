<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('debates_count')->default(0);
            $table->unsignedInteger('arguments_count')->default(0);
            $table->unsignedInteger('poll_votes_count')->default(0);
            $table->unsignedInteger('argument_votes_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('participation')->default(0);
            $table->unsignedInteger('quality')->default(0);
            $table->unsignedSmallInteger('respect')->default(100);
            $table->unsignedInteger('influence')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('topic_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('arguments_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->unsignedInteger('favor_count')->default(0);
            $table->unsignedInteger('contra_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leader_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('participation')->default(0);
            $table->unsignedSmallInteger('respect')->default(100);
            $table->string('status')->default('pending');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leader_candidates');
        Schema::dropIfExists('topic_metrics');
        Schema::dropIfExists('user_metrics');
    }
};
