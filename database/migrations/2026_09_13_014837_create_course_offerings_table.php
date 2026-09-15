<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained('academic_terms')->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('section');
            $table->string('schedule')->nullable();
            $table->string('room')->nullable();
            $table->unsignedInteger('capacity')->default(30);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->unique(['course_id', 'academic_term_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};