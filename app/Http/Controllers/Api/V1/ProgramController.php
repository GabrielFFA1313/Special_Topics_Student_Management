<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
     /**
     * @OA\Get(
     *     path="/programs",
     *     tags={"Programs"},
     *     summary="List programs (search, filter, sort, paginate)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by name or code"),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string"), description="e.g. name or -created_at"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="Paginated list of programs")
     * )
     */
    public function index(Request $request)
    {
        $query = Program::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $sort = $request->query('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        if (in_array($sortColumn, ['name', 'code', 'created_at'])) {
            $query->orderBy($sortColumn, $direction);
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $programs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Programs retrieved successfully.',
            'data' => ProgramResource::collection($programs->items()),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
                'last_page' => $programs->lastPage(),
            ],
        ]);
    }
    /**
     * @OA\Post(
     *     path="/programs",
     *     tags={"Programs"},
     *     summary="Create a new program",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","name"},
     *             @OA\Property(property="code", type="string", example="BSCS"),
     *             @OA\Property(property="name", type="string", example="BS Computer Science"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Program created"),
     *     @OA\Response(response=422, description="Validation failed (e.g. duplicate code)"),
     *     @OA\Response(response=403, description="Forbidden — requires administrator or registrar role")
     * )
     */

    public function store(StoreProgramRequest $request)
    {
        $this->authorize('create', Program::class);
        $program = Program::create($request->validated());
        $program->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Program created successfully.',
            'data' => new ProgramResource($program),
        ], 201);
    }
     /**
     * @OA\Get(
     *     path="/programs/{id}",
     *     tags={"Programs"},
     *     summary="Get a single program by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Program details"),
     *     @OA\Response(response=404, description="Program not found")
     * )
     */

    public function show(Program $program)
    {
        return response()->json([
            'success' => true,
            'message' => 'Program retrieved successfully.',
            'data' => new ProgramResource($program),
        ]);
    }
     /**
     * @OA\Patch(
     *     path="/programs/{id}",
     *     tags={"Programs"},
     *     summary="Update a program",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Program updated"),
     *     @OA\Response(response=404, description="Program not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $this->authorize('update', $program);
        $program->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Program updated successfully.',
            'data' => new ProgramResource($program),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/programs/{id}",
     *     tags={"Programs"},
     *     summary="Delete a program",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Program deleted"),
     *     @OA\Response(response=409, description="Conflict — program has students assigned"),
     *     @OA\Response(response=404, description="Program not found")
     * )
     */

    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);
        if ($program->students()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a program that has students assigned.',
            ], 409);
        }

        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program deleted successfully.',
        ], 204);
    }
}