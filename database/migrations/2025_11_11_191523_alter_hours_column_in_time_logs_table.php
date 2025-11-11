<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            // Change hours column to decimal(5,2)
            $table->decimal('hours', 5, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            // Rollback to integer (if it originally was)
            $table->integer('hours')->change();
        });
    }
};
