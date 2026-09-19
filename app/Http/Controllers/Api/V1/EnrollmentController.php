<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseOffering;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class EnrollmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/enrollments",
     *     tags={"Enrollments"},
     *     summary="List enrollments (filter, paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="student_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="course_offering_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"enrolled","dropped","completed"})),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of enrollments"),
     *     @OA\Response(response=403, description="Forbidden — students may not browse all enrollments")
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Enrollment::class);
        $query = Enrollment::with(['student', 'courseOffering.course', 'grade']);

        if ($studentId = $request->query('student_id')) {
            $query->where('student_id', $studentId);
        }

        if ($offeringId = $request->query('course_offering_id')) {
            $query->where('course_offering_id', $offeringId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $enrollments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Enrollments retrieved successfully.',
            'data' => EnrollmentResource::collection($enrollments->items()),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/enrollments",
     *     tags={"Enrollments"},
     *     summary="Enroll a student in a course offering",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","course_offering_id"},
     *             @OA\Property(property="student_id", type="integer", example=1),
     *             @OA\Property(property="course_offering_id", type="integer", example=1),
     *             @OA\Property(property="enrollment_date", type="string", format="date", nullable=true, description="Defaults to today if omitted"),
     *             @OA\Property(property="status", type="string", enum={"enrolled","dropped","completed"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Enrollment created"),
     *     @OA\Response(response=422, description="Validation failed (e.g. duplicate enrollment, offering at full capacity)"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role")
     * )
     */
    public function store(StoreEnrollmentRequest $request)
    {
        $this->authorize('create', Enrollment::class);
        $data = $request->validated();
        $data['enrollment_date'] = $data['enrollment_date'] ?? now()->toDateString();

        $enrollment = Enrollment::create($data);
        $enrollment->refresh()->load(['student', 'courseOffering.course']);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully.',
            'data' => new EnrollmentResource($enrollment),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/enrollments/{id}",
     *     tags={"Enrollments"},
     *     summary="Get a single enrollment by ID",
     *     description="Instructors may only view enrollments in their own course offerings; students may only view their own enrollments.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Enrollment details"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Enrollment not found")
     * )
     */
    public function show(Enrollment $enrollment)
    {
        $this->authorize('view', $enrollment);
        $enrollment->load(['student', 'courseOffering.course', 'grade']);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment retrieved successfully.',
            'data' => new EnrollmentResource($enrollment),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/enrollments/{id}",
     *     tags={"Enrollments"},
     *     summary="Update an enrollment's status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"enrolled","dropped","completed"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Enrollment status updated"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role"),
     *     @OA\Response(response=404, description="Enrollment not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $this->authorize('update', $enrollment);
        $validated = $request->validate([
            'status' => ['required', 'in:enrolled,dropped,completed'],
        ]);

        $enrollment->update($validated);
        $enrollment->load(['student', 'courseOffering.course', 'grade']);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment status updated successfully.',
            'data' => new EnrollmentResource($enrollment),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/enrollments/{id}",
     *     tags={"Enrollments"},
     *     summary="Delete an enrollment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Enrollment deleted"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role"),
     *     @OA\Response(response=409, description="Conflict — enrollment already has a grade recorded"),
     *     @OA\Response(response=404, description="Enrollment not found")
     * )
     */
    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('delete', $enrollment);
        if ($enrollment->grade()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an enrollment that already has a grade recorded.',
            ], 409);
        }

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enrollment deleted successfully.',
        ], 204);
    }

    /**
     * @OA\Get(
     *     path="/students/{id}/enrollments",
     *     tags={"Enrollments"},
     *     summary="Get all enrollments for a specific student",
     *     description="Students may only view their own enrollments (object-level authorization).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of the student's enrollments"),
     *     @OA\Response(response=403, description="Forbidden — a student may not view another student's enrollments"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function forStudent(Student $student)
    {
        $this->authorize('view', $student); // reuses the same "own profile" rule

        $enrollments = $student->enrollments()
            ->with(['courseOffering.course', 'grade'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Student enrollments retrieved successfully.',
            'data' => EnrollmentResource::collection($enrollments->items()),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/course-offerings/{id}/students",
     *     tags={"Enrollments"},
     *     summary="Get all students enrolled in a specific course offering",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of enrolled students"),
     *     @OA\Response(response=404, description="Course offering not found")
     * )
     */
    public function forCourseOffering(CourseOffering $courseOffering)
    {
        $enrollments = $courseOffering->enrollments()
            ->with(['student', 'grade'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Enrolled students retrieved successfully.',
            'data' => EnrollmentResource::collection($enrollments->items()),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage(),
            ],
        ]);
    }
}