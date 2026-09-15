<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('course_title', 'like', "%{$search}%")
                  ->orWhere('course_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sort = $request->query('sort', 'course_code');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        if (in_array($sortColumn, ['course_code', 'course_title', 'units', 'created_at'])) {
            $query->orderBy($sortColumn, $direction);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $courses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully.',
            'data' => CourseResource::collection($courses->items()),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
            ],
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $course = Course::create($request->validated());
        $course->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'data' => new CourseResource($course),
        ], 201);
    }

    public function show(Course $course)
    {
        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $course->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    public function destroy(Course $course)
    {
        if ($course->courseOfferings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a course that has offerings.',
            ], 409);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.',
        ], 204);
    }
}