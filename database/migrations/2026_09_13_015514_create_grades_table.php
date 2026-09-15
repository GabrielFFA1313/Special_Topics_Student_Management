<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->decimal('midterm_grade', 4, 2)->nullable();
            $table->decimal('final_grade', 4, 2)->nullable();
            $table->string('remarks')->nullable(); // passed, failed, incomplete
            $table->timestamps();

            // one enrollment = one grade record ("may have one grade record")
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};