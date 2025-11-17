<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type', 100);
            $table->text('reason')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
        });

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

        Schema::create('leave_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->cascadeOnDelete();
            $table->string('action'); // Approved | Rejected
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            $table->index(['leave_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('leave_counters');
        Schema::dropIfExists('leave_status_histories');
    }
};
