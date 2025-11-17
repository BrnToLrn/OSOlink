<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();

            $table->date('period_from');
            $table->date('period_to');
            $table->date('issue_date');

            $table->decimal('hours_worked', 8, 2);
            $table->decimal('hourly_rate', 10, 2);

            $table->decimal('gross_pay', 10, 2);
            $table->decimal('adjustments', 10, 2)->default(0);

            // Cash loan installment info (optional if user has a loan)
            $table->foreignId('cash_loan_id')->nullable()->constrained('cash_loans')->nullOnDelete();

            // Which period of the loan this payslip represents (1..pay_periods)
            $table->unsignedTinyInteger('cash_loan_period_number')->nullable();

            // Deduction amount for this period (loan total / pay_periods)
            $table->decimal('cash_loan_period_deduction', 10, 2)->default(0);

            $table->decimal('net_pay', 10, 2);

            // Paid flag (default false as requested)
            $table->boolean('is_paid')->default(false);

            $table->timestamps();

            $table->index(['cash_loan_id', 'cash_loan_period_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};