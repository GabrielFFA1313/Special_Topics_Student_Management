<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class StudentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/students",
     *     tags={"Students"},
     *     summary="List students (search, filter, sort, paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by first name, last name, or student number"),
     *     @OA\Parameter(name="program_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year_level", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive","graduated","dropped"})),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string"), description="e.g. last_name or -created_at"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of students with nested program data"),
     *     @OA\Response(response=403, description="Forbidden — students may not browse the full list")
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);
        $query = Student::with('program');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        if ($programId = $request->query('program_id')) {
            $query->where('program_id', $programId);
        }

        if ($yearLevel = $request->query('year_level')) {
            $query->where('year_level', $yearLevel);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sort = $request->query('sort', 'last_name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        if (in_array($sortColumn, ['last_name', 'first_name', 'student_number', 'year_level', 'created_at'])) {
            $query->orderBy($sortColumn, $direction);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $students = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved successfully.',
            'data' => StudentResource::collection($students->items()),
            'meta' => [
                'current_page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/students",
     *     tags={"Students"},
     *     summary="Create a new student",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_number","first_name","last_name","program_id","year_level"},
     *             @OA\Property(property="student_number", type="string", example="2026-00001"),
     *             @OA\Property(property="first_name", type="string", example="Juan"),
     *             @OA\Property(property="middle_name", type="string", nullable=true),
     *             @OA\Property(property="last_name", type="string", example="Dela Cruz"),
     *             @OA\Property(property="suffix", type="string", nullable=true),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="contact_number", type="string", nullable=true),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="program_id", type="integer", example=1),
     *             @OA\Property(property="year_level", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"active","inactive","graduated","dropped"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Student created"),
     *     @OA\Response(response=422, description="Validation failed (e.g. duplicate student_number, invalid program_id)"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role")
     * )
     */
    public function store(StoreStudentRequest $request)
    {
        $this->authorize('create', Student::class);
        $student = Student::create($request->validated());
        $student->refresh()->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully.',
            'data' => new StudentResource($student),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/students/{id}",
     *     tags={"Students"},
     *     summary="Get a single student by ID",
     *     description="Students may only view their own profile (object-level authorization).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Student details"),
     *     @OA\Response(response=403, description="Forbidden — a student may not view another student's profile"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function show(Student $student)
    {
        $this->authorize('view', $student);
        $student->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Student retrieved successfully.',
            'data' => new StudentResource($student),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/students/{id}",
     *     tags={"Students"},
     *     summary="Update a student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="student_number", type="string"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="program_id", type="integer"),
     *             @OA\Property(property="year_level", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive","graduated","dropped"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Student updated"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $this->authorize('update', $student);
        $student->update($request->validated());
        $student->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
            'data' => new StudentResource($student),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/students/{id}",
     *     tags={"Students"},
     *     summary="Delete a student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Student deleted"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role"),
     *     @OA\Response(response=409, description="Conflict — student has enrollment records"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);
        if ($student->enrollments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a student that has enrollment records.',
            ], 409);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully.',
        ], 204);
    }

    /**
     * @OA\Get(
     *     path="/students/{id}/academic-record",
     *     tags={"Students"},
     *     summary="Get a student's academic record grouped by academic term",
     *     description="Students may only view their own academic record (object-level authorization).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Academic record grouped by term, including grades per course"),
     *     @OA\Response(response=403, description="Forbidden — a student may not view another student's record"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function academicRecord(Student $student)
    {
        $this->authorize('view', $student); // same rule as viewing the profile
        $student->load('program');

        $enrollments = $student->enrollments()
            ->with(['courseOffering.course', 'courseOffering.academicTerm', 'grade'])
            ->get();

        $groupedByTerm = $enrollments->groupBy(fn ($enrollment) => $enrollment->courseOffering->academicTerm->id);

        $academicRecord = $groupedByTerm->map(function ($termEnrollments) {
            $term = $termEnrollments->first()->courseOffering->academicTerm;

            return [
                'academic_term' => [
                    'id' => $term->id,
                    'academic_year' => $term->academic_year,
                    'semester' => $term->semester,
                ],
                'courses' => $termEnrollments->map(function ($enrollment) {
                    return [
                        'course_code' => $enrollment->courseOffering->course->course_code,
                        'course_title' => $enrollment->courseOffering->course->course_title,
                        'units' => $enrollment->courseOffering->course->units,
                        'section' => $enrollment->courseOffering->section,
                        'enrollment_status' => $enrollment->status,
                        'midterm_grade' => $enrollment->grade?->midterm_grade,
                        'final_grade' => $enrollment->grade?->final_grade,
                        'remarks' => $enrollment->grade?->remarks,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Academic record retrieved successfully.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                    'program' => $student->program->name,
                    'year_level' => $student->year_level,
                ],
                'academic_record' => $academicRecord,
            ],
        ]);
    }
}