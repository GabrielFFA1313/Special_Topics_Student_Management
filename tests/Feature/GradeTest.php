<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_grade_enrollment_in_their_own_offering(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $offering = CourseOffering::factory()->create(['instructor_id' => $instructor->id]);
        $enrollment = Enrollment::factory()->create(['course_offering_id' => $offering->id]);

        $response = $this->actingAs($instructor, 'sanctum')->postJson('/api/v1/grades', [
            'enrollment_id' => $enrollment->id,
            'midterm_grade' => 1.5,
        ]);

        $response->assertStatus(201);
    }

    public function test_instructor_cannot_grade_enrollment_in_someone_elses_offering(): void
    {
        $instructorA = User::factory()->create(['role' => 'instructor']);
        $instructorB = User::factory()->create(['role' => 'instructor']);
        $offering = CourseOffering::factory()->create(['instructor_id' => $instructorB->id]);
        $enrollment = Enrollment::factory()->create(['course_offering_id' => $offering->id]);

        $response = $this->actingAs($instructorA, 'sanctum')->postJson('/api/v1/grades', [
            'enrollment_id' => $enrollment->id,
            'midterm_grade' => 1.5,
        ]);

        $response->assertStatus(403);
    }

    public function test_grading_fails_for_nonexistent_enrollment(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $response = $this->actingAs($instructor, 'sanctum')->postJson('/api/v1/grades', [
            'enrollment_id' => 99999,
            'midterm_grade' => 1.5,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('enrollment_id');
    }
}