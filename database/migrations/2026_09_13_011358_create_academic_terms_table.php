<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year'); // e.g. "2026-2027"
            $table->string('semester');      // e.g. "1st Semester"
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('upcoming');
            $table->timestamps();

            $table->unique(['academic_year', 'semester']); // no duplicate terms
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};