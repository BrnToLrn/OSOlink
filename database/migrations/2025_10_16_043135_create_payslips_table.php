<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            $table->date('issue_date');
            $table->decimal('hours_worked', 8, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('adjustments', 10, 2)->default(0);
            $table->decimal('net_pay', 10, 2);
            // $table->boolean('is_paid')->default(false);
            // foreign id cash loan periodly deductions;  divide by how many periods to be paid off
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};