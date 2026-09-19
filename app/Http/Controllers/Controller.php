<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Student Information Management API",
 *     version="1.0.0",
 *     description="Backend REST API for managing students, programs, courses, academic terms, course offerings, enrollments, and grades."
 * )
 * @OA\Server(
 *     url="/api/v1",
 *     description="Local development server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum token"
 * )
 * @OA\Tag(name="Authentication", description="Login, logout, current user")
 * @OA\Tag(name="Programs", description="Academic program management")
 * @OA\Tag(name="Courses", description="Course management")
 * @OA\Tag(name="Academic Terms", description="Academic term management")
 * @OA\Tag(name="Students", description="Student record management")
 * @OA\Tag(name="Course Offerings", description="Course offering management")
 * @OA\Tag(name="Enrollments", description="Student enrollment management")
 * @OA\Tag(name="Grades", description="Grade recording and retrieval")
 */
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}