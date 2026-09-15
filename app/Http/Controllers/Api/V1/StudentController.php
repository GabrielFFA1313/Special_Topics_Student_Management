<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
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

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());
        $student->refresh()->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully.',
            'data' => new StudentResource($student),
        ], 201);
    }

    public function show(Student $student)
    {
        $student->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Student retrieved successfully.',
            'data' => new StudentResource($student),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());
        $student->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
            'data' => new StudentResource($student),
        ]);
    }

    public function destroy(Student $student)
    {
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
    public function academicRecord(Student $student)
{
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