<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
        Schema::dropIfExists('leave_status_histories');
    }
};