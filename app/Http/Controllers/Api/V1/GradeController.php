<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class GradeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/grades",
     *     tags={"Grades"},
     *     summary="List grades (paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of grades"),
     *     @OA\Response(response=403, description="Forbidden — students may not browse all grades")
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Grade::class);
        $query = Grade::with('enrollment.student');

        $perPage = min((int) $request->query('per_page', 15), 100);
        $grades = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Grades retrieved successfully.',
            'data' => GradeResource::collection($grades->items()),
            'meta' => [
                'current_page' => $grades->currentPage(),
                'per_page' => $grades->perPage(),
                'total' => $grades->total(),
                'last_page' => $grades->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/grades",
     *     tags={"Grades"},
     *     summary="Record a grade for an enrollment",
     *     description="Instructors may only grade enrollments belonging to their own course offerings.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"enrollment_id"},
     *             @OA\Property(property="enrollment_id", type="integer", example=1),
     *             @OA\Property(property="midterm_grade", type="number", format="float", nullable=true, example=1.5),
     *             @OA\Property(property="final_grade", type="number", format="float", nullable=true, example=1.75),
     *             @OA\Property(property="remarks", type="string", enum={"passed","failed","incomplete"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Grade recorded"),
     *     @OA\Response(response=422, description="Validation failed (e.g. enrollment already has a grade)"),
     *     @OA\Response(response=403, description="Forbidden — instructor does not teach this enrollment's course offering")
     * )
     */
    public function store(StoreGradeRequest $request)
    {
        $this->authorize('create', Grade::class);

        // Fine-grained instructor scope check (can't be expressed in the policy's
        // create() since there's no Grade instance yet — we check the enrollment instead)
        if (auth()->user()->isInstructor()) {
            $enrollment = \App\Models\Enrollment::with('courseOffering')->find($request->enrollment_id);
            if (! $enrollment || $enrollment->courseOffering->instructor_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You may only grade enrollments in your own course offerings.',
                ], 403);
            }
        }
        $grade = Grade::create($request->validated());
        $grade->refresh()->load('enrollment');

        return response()->json([
            'success' => true,
            'message' => 'Grade recorded successfully.',
            'data' => new GradeResource($grade),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/grades/{id}",
     *     tags={"Grades"},
     *     summary="Get a single grade by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Grade details"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Grade not found")
     * )
     */
    public function show(Grade $grade)
    {
        $this->authorize('view', $grade);
        $grade->load('enrollment');

        return response()->json([
            'success' => true,
            'message' => 'Grade retrieved successfully.',
            'data' => new GradeResource($grade),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/grades/{id}",
     *     tags={"Grades"},
     *     summary="Update a grade",
     *     description="Instructors may only update grades in their own course offerings.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="midterm_grade", type="number", format="float", nullable=true),
     *             @OA\Property(property="final_grade", type="number", format="float", nullable=true),
     *             @OA\Property(property="remarks", type="string", enum={"passed","failed","incomplete"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Grade updated"),
     *     @OA\Response(response=403, description="Forbidden — instructor does not teach this enrollment's course offering"),
     *     @OA\Response(response=404, description="Grade not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UpdateGradeRequest $request, Grade $grade)
    {
        $grade->update($request->validated());
        $grade->load('enrollment');

        return response()->json([
            'success' => true,
            'message' => 'Grade updated successfully.',
            'data' => new GradeResource($grade),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/students/{id}/grades",
     *     tags={"Grades"},
     *     summary="Get all grades for a specific student",
     *     description="Students may only view their own grades (object-level authorization).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of the student's grades"),
     *     @OA\Response(response=403, description="Forbidden — a student may not view another student's grades"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function forStudent(Student $student)
    {
        $this->authorize('view', $student);
        $grades = Grade::whereHas('enrollment', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->with('enrollment.courseOffering.course')->get();

        return response()->json([
            'success' => true,
            'message' => 'Student grades retrieved successfully.',
            'data' => GradeResource::collection($grades),
        ]);
    }
}