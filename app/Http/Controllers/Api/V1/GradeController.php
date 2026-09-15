<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;

class GradeController extends Controller
{
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

    // GET /students/{id}/grades — from section 8.1
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