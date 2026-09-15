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

    public function show(Program $program)
    {
        return response()->json([
            'success' => true,
            'message' => 'Program retrieved successfully.',
            'data' => new ProgramResource($program),
        ]);
    }

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