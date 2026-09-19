<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Http\Resources\AcademicTermResource;
use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AcademicTermController extends Controller
{
    /**
     * @OA\Get(
     *     path="/academic-terms",
     *     tags={"Academic Terms"},
     *     summary="List academic terms (search, filter, sort, paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by academic year or semester"),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"upcoming","active","closed"})),
     *     @OA\Parameter(name="academic_year", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string"), description="e.g. start_date or -start_date"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of academic terms")
     * )
     */
    public function index(Request $request)
    {
        $query = AcademicTerm::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($year = $request->query('academic_year')) {
            $query->where('academic_year', $year);
        }

        $sort = $request->query('sort', '-start_date');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        if (in_array($sortColumn, ['start_date', 'academic_year', 'created_at'])) {
            $query->orderBy($sortColumn, $direction);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $terms = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Academic terms retrieved successfully.',
            'data' => AcademicTermResource::collection($terms->items()),
            'meta' => [
                'current_page' => $terms->currentPage(),
                'per_page' => $terms->perPage(),
                'total' => $terms->total(),
                'last_page' => $terms->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/academic-terms",
     *     tags={"Academic Terms"},
     *     summary="Create a new academic term",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"academic_year","semester","start_date","end_date"},
     *             @OA\Property(property="academic_year", type="string", example="2026-2027"),
     *             @OA\Property(property="semester", type="string", example="1st Semester"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-08-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-12-15"),
     *             @OA\Property(property="status", type="string", enum={"upcoming","active","closed"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Academic term created"),
     *     @OA\Response(response=422, description="Validation failed (e.g. duplicate academic_year + semester combination, or end_date before start_date)"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role")
     * )
     */
    public function store(StoreAcademicTermRequest $request)
    {
        $term = AcademicTerm::create($request->validated());
        $term->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Academic term created successfully.',
            'data' => new AcademicTermResource($term),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/academic-terms/{id}",
     *     tags={"Academic Terms"},
     *     summary="Get a single academic term by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Academic term details"),
     *     @OA\Response(response=404, description="Academic term not found")
     * )
     */
    public function show(AcademicTerm $academicTerm)
    {
        return response()->json([
            'success' => true,
            'message' => 'Academic term retrieved successfully.',
            'data' => new AcademicTermResource($academicTerm),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/academic-terms/{id}",
     *     tags={"Academic Terms"},
     *     summary="Update an academic term",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="academic_year", type="string"),
     *             @OA\Property(property="semester", type="string"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"upcoming","active","closed"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Academic term updated"),
     *     @OA\Response(response=404, description="Academic term not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(UpdateAcademicTermRequest $request, AcademicTerm $academicTerm)
    {
        $academicTerm->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic term updated successfully.',
            'data' => new AcademicTermResource($academicTerm),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/academic-terms/{id}",
     *     tags={"Academic Terms"},
     *     summary="Delete an academic term",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Academic term deleted"),
     *     @OA\Response(response=409, description="Conflict — academic term has course offerings"),
     *     @OA\Response(response=404, description="Academic term not found")
     * )
     */
    public function destroy(AcademicTerm $academicTerm)
    {
        if ($academicTerm->courseOfferings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an academic term that has course offerings.',
            ], 409);
        }

        $academicTerm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Academic term deleted successfully.',
        ], 204);
    }
}