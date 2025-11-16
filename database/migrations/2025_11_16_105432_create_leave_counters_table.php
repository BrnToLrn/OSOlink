<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type', 100);
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('allowance')->default(0);
            $table->unsignedSmallInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'leave_type', 'year'], 'user_type_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_counters');
    }
};