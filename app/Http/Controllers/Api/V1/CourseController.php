<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CourseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/courses",
     *     tags={"Courses"},
     *     summary="List courses (search, filter, sort, paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by course title or code"),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string"), description="e.g. course_code or -created_at"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of courses")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/courses",
     *     tags={"Courses"},
     *     summary="Create a new course",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_code","course_title","units"},
     *             @OA\Property(property="course_code", type="string", example="CS101"),
     *             @OA\Property(property="course_title", type="string", example="Intro to Programming"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="units", type="integer", example=3),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Course created"),
     *     @OA\Response(response=422, description="Validation failed (e.g. duplicate course_code)"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/courses/{id}",
     *     tags={"Courses"},
     *     summary="Get a single course by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Course details"),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
    public function show(Course $course)
    {
        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/courses/{id}",
     *     tags={"Courses"},
     *     summary="Update a course",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="course_code", type="string"),
     *             @OA\Property(property="course_title", type="string"),
     *             @OA\Property(property="units", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Course updated"),
     *     @OA\Response(response=404, description="Course not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $course->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/courses/{id}",
     *     tags={"Courses"},
     *     summary="Delete a course",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Course deleted"),
     *     @OA\Response(response=409, description="Conflict — course has offerings"),
     *     @OA\Response(response=404, description="Course not found")
     * )
     */
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