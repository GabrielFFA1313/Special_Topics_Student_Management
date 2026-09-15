<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseOffering;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
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

    public function store(StoreEnrollmentRequest $request)
    {
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

    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'courseOffering.course', 'grade']);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment retrieved successfully.',
            'data' => new EnrollmentResource($enrollment),
        ]);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
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

    public function destroy(Enrollment $enrollment)
    {
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

    // GET /students/{id}/enrollments — nested route from section 8.1
    public function forStudent(Student $student)
    {
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

    // GET /course-offerings/{id}/students — nested route from section 8.1
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