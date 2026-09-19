<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_valid_student(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $program = Program::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/students', [
            'student_number' => '2026-00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'program_id' => $program->id,
            'year_level' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.student_number', '2026-00001');

        $this->assertDatabaseHas('students', ['student_number' => '2026-00001']);
    }

    public function test_creating_student_fails_with_duplicate_student_number(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $program = Program::factory()->create();
        Student::factory()->create(['student_number' => '2026-00001', 'program_id' => $program->id]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/students', [
            'student_number' => '2026-00001',
            'first_name' => 'Another',
            'last_name' => 'Student',
            'program_id' => $program->id,
            'year_level' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('student_number');
    }

    public function test_creating_student_fails_with_invalid_email(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $program = Program::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/students', [
            'student_number' => '2026-00002',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'not-an-email',
            'program_id' => $program->id,
            'year_level' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_creating_student_fails_with_nonexistent_program(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/students', [
            'student_number' => '2026-00003',
            'first_name' => 'Test',
            'last_name' => 'User',
            'program_id' => 99999,
            'year_level' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('program_id');
    }

    public function test_admin_can_retrieve_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $student = Student::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/students/{$student->id}");

        $response->assertStatus(200)->assertJsonPath('data.id', $student->id);
    }

    public function test_retrieving_nonexistent_student_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/students/99999');

        $response->assertStatus(404);
    }

    public function test_admin_can_update_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $student = Student::factory()->create(['year_level' => 1]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/students/{$student->id}", [
            'year_level' => 2,
        ]);

        $response->assertStatus(200)->assertJsonPath('data.year_level', 2);
    }
}