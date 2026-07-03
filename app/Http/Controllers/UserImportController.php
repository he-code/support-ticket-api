<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUsersRequest;
use App\Http\Resources\UserImportResource;
use App\Models\UserImport;
use App\Services\UserImportService;
use Illuminate\Http\Request;
use RuntimeException;

class UserImportController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $imports = UserImport::query()->latest()->paginate(10);

        return response()->json([
            'imports' => UserImportResource::collection($imports),
            'pagination' => [
                'total' => $imports->total(),
                'per_page' => $imports->perPage(),
                'current_page' => $imports->currentPage(),
                'last_page' => $imports->lastPage(),
                'from' => $imports->firstItem(),
                'to' => $imports->lastItem(),
            ],
        ]);
    }

    public function store(ImportUsersRequest $request, UserImportService $importService)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $import = $importService->import(
                $request->file('file'),
                $request->user()->id,
                $request->boolean('update_existing'),
                $request->input('default_password')
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Users imported successfully',
            'import' => new UserImportResource($import),
        ], 201);
    }
}
