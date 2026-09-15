<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Http\Resources\AcademicTermResource;
use App\Models\AcademicTerm;
use Illuminate\Http\Request;

class AcademicTermController extends Controller
{
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

    public function show(AcademicTerm $academicTerm)
    {
        return response()->json([
            'success' => true,
            'message' => 'Academic term retrieved successfully.',
            'data' => new AcademicTermResource($academicTerm),
        ]);
    }

    public function update(UpdateAcademicTermRequest $request, AcademicTerm $academicTerm)
    {
        $academicTerm->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic term updated successfully.',
            'data' => new AcademicTermResource($academicTerm),
        ]);
    }

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