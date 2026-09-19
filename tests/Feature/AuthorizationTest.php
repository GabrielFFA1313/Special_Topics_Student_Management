<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_role_cannot_create_a_program(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student, 'sanctum')->postJson('/api/v1/programs', [
            'code' => 'BSIT',
            'name' => 'BS IT',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_a_program(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/programs', [
            'code' => 'BSIT',
            'name' => 'BS IT',
        ]);

        $response->assertStatus(201);
    }

    public function test_student_cannot_view_another_students_profile(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $ownStudent = Student::factory()->create(['user_id' => $studentUser->id]);
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($studentUser, 'sanctum')->getJson("/api/v1/students/{$otherStudent->id}");

        $response->assertStatus(403);
    }

    public function test_student_can_view_their_own_profile(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $ownStudent = Student::factory()->create(['user_id' => $studentUser->id]);

        $response = $this->actingAs($studentUser, 'sanctum')->getJson("/api/v1/students/{$ownStudent->id}");

        $response->assertStatus(200);
    }
}