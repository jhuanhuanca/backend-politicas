<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arguments', function (Blueprint $table) {
            $table->unsignedInteger('favor_count')->default(0);
            $table->unsignedInteger('contra_count')->default(0);
        });

        Schema::create('argument_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('argument_id')->constrained()->cascadeOnDelete();
            $table->string('stance');
            $table->timestamps();

            $table->unique(['user_id', 'argument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('argument_votes');

        Schema::table('arguments', function (Blueprint $table) {
            $table->dropColumn(['favor_count', 'contra_count']);
        });
    }
};
