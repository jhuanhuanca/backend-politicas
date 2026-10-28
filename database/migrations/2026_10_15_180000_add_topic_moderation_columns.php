<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('shares_count');
            $table->timestamp('featured_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn(['closed_at', 'featured_at']);
        });
    }
};
