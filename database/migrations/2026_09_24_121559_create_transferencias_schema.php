<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            'DROP SCHEMA IF EXISTS transferencias CASCADE'
        );
        DB::statement(
            'CREATE SCHEMA transferencias'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'DROP SCHEMA IF EXISTS transferencias CASCADE'
        );
    }
};
