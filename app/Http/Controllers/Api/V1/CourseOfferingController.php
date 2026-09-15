<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseOfferingRequest;
use App\Http\Requests\UpdateCourseOfferingRequest;
use App\Http\Resources\CourseOfferingResource;
use App\Models\CourseOffering;
use Illuminate\Http\Request;

class CourseOfferingController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseOffering::with(['course', 'academicTerm', 'instructor'])
            ->withCount('enrollments');

        if ($courseId = $request->query('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($termId = $request->query('academic_term_id')) {
            $query->where('academic_term_id', $termId);
        }

        if ($instructorId = $request->query('instructor_id')) {
            $query->where('instructor_id', $instructorId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sort = $request->query('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        if (in_array($sortColumn, ['section', 'capacity', 'created_at'])) {
            $query->orderBy($sortColumn, $direction);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $offerings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Course offerings retrieved successfully.',
            'data' => CourseOfferingResource::collection($offerings->items()),
            'meta' => [
                'current_page' => $offerings->currentPage(),
                'per_page' => $offerings->perPage(),
                'total' => $offerings->total(),
                'last_page' => $offerings->lastPage(),
            ],
        ]);
    }

    public function store(StoreCourseOfferingRequest $request)
    {
        $offering = CourseOffering::create($request->validated());
        $offering->refresh()->load(['course', 'academicTerm', 'instructor'])->loadCount('enrollments');

        return response()->json([
            'success' => true,
            'message' => 'Course offering created successfully.',
            'data' => new CourseOfferingResource($offering),
        ], 201);
    }

    public function show(CourseOffering $courseOffering)
    {
        $courseOffering->load(['course', 'academicTerm', 'instructor'])->loadCount('enrollments');

        return response()->json([
            'success' => true,
            'message' => 'Course offering retrieved successfully.',
            'data' => new CourseOfferingResource($courseOffering),
        ]);
    }

    public function update(UpdateCourseOfferingRequest $request, CourseOffering $courseOffering)
    {
        $courseOffering->update($request->validated());
        $courseOffering->load(['course', 'academicTerm', 'instructor'])->loadCount('enrollments');

        return response()->json([
            'success' => true,
            'message' => 'Course offering updated successfully.',
            'data' => new CourseOfferingResource($courseOffering),
        ]);
    }

    public function destroy(CourseOffering $courseOffering)
    {
        if ($courseOffering->enrollments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a course offering that has enrollments.',
            ], 409);
        }

        $courseOffering->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course offering deleted successfully.',
        ], 204);
    }
}