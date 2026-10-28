<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('feed_url')->unique();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::table('news_items', function (Blueprint $table) {
            $table->foreignId('news_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guid')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('news_source_id');
            $table->dropUnique(['guid']);
            $table->dropColumn('guid');
        });

        Schema::dropIfExists('news_sources');
    }
};
