<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('email')->nullable()->unique()->after('name');
            $table->string('department', 60)->nullable()->after('nick');
            $table->unsignedSmallInteger('birth_year')->nullable()->after('department');
            $table->string('password')->nullable()->after('birth_year');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['name', 'email', 'department', 'birth_year', 'password']);
        });
    }
};
