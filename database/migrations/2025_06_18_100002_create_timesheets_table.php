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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('date');
            $table->timestamp('in_at')->nullable();
            $table->timestamp('out_at')->nullable();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_shift_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index('uuid');
            $table->index(['employee_id', 'date']);
            $table->index('in_at');
            $table->index('out_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
