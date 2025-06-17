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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('salutation', ['Mr.', 'Mrs.', 'Miss.', 'Dr.']);
            $table->string('full_name');
            $table->string('emp_id')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('designation_id')->constrained()->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('dob')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->date('date_of_leaving')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->enum('gender', ['male', 'female', 'others']);
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('address')->nullable();
            $table->string('fathers_name')->nullable();
            $table->string('mothers_name')->nullable();
            $table->string('emergency_contact_no')->nullable();
            $table->string('photograph')->nullable();
            $table->timestamps();

            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
