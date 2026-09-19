<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enroll_a_student_in_a_course_offering(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $student = Student::factory()->create();
        $offering = CourseOffering::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/enrollments', [
            'student_id' => $student->id,
            'course_offering_id' => $offering->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_offering_id' => $offering->id,
        ]);
    }

    public function test_duplicate_enrollment_is_prevented(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $student = Student::factory()->create();
        $offering = CourseOffering::factory()->create();

        // first enrollment succeeds
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/enrollments', [
            'student_id' => $student->id,
            'course_offering_id' => $offering->id,
        ])->assertStatus(201);

        // second identical enrollment must fail
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/enrollments', [
            'student_id' => $student->id,
            'course_offering_id' => $offering->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('enrollments', 1); // still only one row
    }

    public function test_enrollment_fails_with_invalid_student_id(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $offering = CourseOffering::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/enrollments', [
            'student_id' => 99999,
            'course_offering_id' => $offering->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('student_id');
    }
}