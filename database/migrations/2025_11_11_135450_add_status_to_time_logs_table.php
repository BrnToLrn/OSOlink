<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Approved', 'Declined'])->default('Pending');
            $table->text('decline_reason')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
        $table->dropColumn(['status', 'decline_reason', 'time_in', 'time_out']);
        });
    }
};
