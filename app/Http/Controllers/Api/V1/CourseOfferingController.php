<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseOfferingRequest;
use App\Http\Requests\UpdateCourseOfferingRequest;
use App\Http\Resources\CourseOfferingResource;
use App\Models\CourseOffering;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CourseOfferingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/course-offerings",
     *     tags={"Course Offerings"},
     *     summary="List course offerings (search, filter, sort, paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by section or the related course's code/title"),
     *     @OA\Parameter(name="course_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="academic_term_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="instructor_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"open","closed","cancelled"})),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string"), description="e.g. section or -created_at"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of course offerings, each including nested course, academic term, instructor, and enrolled_count")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/course-offerings",
     *     tags={"Course Offerings"},
     *     summary="Create a new course offering",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"course_id","academic_term_id","section","capacity"},
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="academic_term_id", type="integer", example=1),
     *             @OA\Property(property="instructor_id", type="integer", nullable=true, example=2, description="Must reference a user with role=instructor"),
     *             @OA\Property(property="section", type="string", example="A"),
     *             @OA\Property(property="schedule", type="string", nullable=true, example="MWF 9:00-10:00"),
     *             @OA\Property(property="room", type="string", nullable=true, example="Room 201"),
     *             @OA\Property(property="capacity", type="integer", example=30),
     *             @OA\Property(property="status", type="string", enum={"open","closed","cancelled"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Course offering created"),
     *     @OA\Response(response=422, description="Validation failed (e.g. invalid instructor role, duplicate section for the same course/term)"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/course-offerings/{id}",
     *     tags={"Course Offerings"},
     *     summary="Get a single course offering by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Course offering details"),
     *     @OA\Response(response=404, description="Course offering not found")
     * )
     */
    public function show(CourseOffering $courseOffering)
    {
        $courseOffering->load(['course', 'academicTerm', 'instructor'])->loadCount('enrollments');

        return response()->json([
            'success' => true,
            'message' => 'Course offering retrieved successfully.',
            'data' => new CourseOfferingResource($courseOffering),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/course-offerings/{id}",
     *     tags={"Course Offerings"},
     *     summary="Update a course offering",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="course_id", type="integer"),
     *             @OA\Property(property="academic_term_id", type="integer"),
     *             @OA\Property(property="instructor_id", type="integer", nullable=true),
     *             @OA\Property(property="section", type="string"),
     *             @OA\Property(property="schedule", type="string", nullable=true),
     *             @OA\Property(property="room", type="string", nullable=true),
     *             @OA\Property(property="capacity", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"open","closed","cancelled"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Course offering updated"),
     *     @OA\Response(response=404, description="Course offering not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/course-offerings/{id}",
     *     tags={"Course Offerings"},
     *     summary="Delete a course offering",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Course offering deleted"),
     *     @OA\Response(response=409, description="Conflict — course offering has enrollments"),
     *     @OA\Response(response=404, description="Course offering not found")
     * )
     */
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