<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('requester_user_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_date')->default(false);
            $table->enum('half_day_shift', ['na', 'an', 'fn'])->default('na');
            $table->text('reason');
            $table->text('alternate_arrangement')->nullable();
            $table->enum('status', ['approved', 'requested', 'withdrawn', 'rejected'])->default('requested');
            $table->string('leave_file')->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approver_comment')->nullable();
            $table->timestamps();

            $table->index('uuid');
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
