<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Projects Table
         */
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['Not Started', 'In Progress', 'On Hold', 'Completed'])->default('Not Started');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // admin who created it
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // optional assigned user
            $table->timestamps();
        });

        /**
         * Pivot Table: project_user
         */
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        /**
         * Time Logs Table
         */
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('hours');
            $table->text('work_output')->nullable();
            $table->date('date')->nullable(); // merged add_date_to_time_logs
            $table->timestamps();
        });

        /**
         * Comments Table
         */
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade'); // merged add_parent_id_to_comments
            $table->text('content');
            $table->timestamps();
        });

        /**
         * Project Permissions Table
         */
        Schema::create('project_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['viewer', 'editor', 'manager'])->default('viewer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_permissions');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('time_logs');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }
};
