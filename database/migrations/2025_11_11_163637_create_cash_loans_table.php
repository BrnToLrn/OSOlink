<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Fields (aligned with leaves)
            $table->date('date_requested');
            $table->decimal('amount', 10, 2);
            $table->string('type', 100);                 // Emergency, Personal, etc.
            $table->string('status', 50)->default('Pending');     // Pending, Approved, Denied
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('date_requested');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_loans');
    }
};