<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->date('date_of_birth');
            $table->timestamps();
        });

        Schema::create('hourly_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('old_rate', 10, 2);
            $table->decimal('new_rate', 10, 2);
            $table->timestamp('time_changed')->useCurrent();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('dependents');
        Schema::dropIfExists('hourly_rate_history');
    }
};
