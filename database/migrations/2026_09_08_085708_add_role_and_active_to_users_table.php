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
        Schema::table('users', function (Blueprint $table) {
            // add role column with default role value as 'user'
            $table->enum('role', ['administrator', 'manager', 'user'])
            ->default('user')
            ->after('password');

            // add active column with default value as true
            $table->boolean('active')
            ->default(true)
            ->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropColumn('active');
        });
    }
};
