<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->restrictOnDelete(); // prevent deleting a program that has students

            $table->unsignedTinyInteger('year_level')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();

            // optionally link a student to a login account
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['last_name', 'first_name']); // supports search
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};