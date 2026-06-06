<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCategoryRequest;
use App\Http\Requests\UpdateTicketCategoryRequest;
use App\Http\Resources\TicketCategoryResource;
use App\Models\TicketCategory;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    // Listar categorías activas o todas si el admin lo solicita
    public function index(Request $request)
    {
        $query = TicketCategory::query()->orderBy('name');

        // Usuarios normales solo ven categorías activas
        if (! $request->user()->isAdmin()) {
            $query->where('is_active', true);
        }

        $categories = $query->get();

        return response()->json([
            'categories' => TicketCategoryResource::collection($categories),
        ]);
    }

    // Crear categoría
    public function store(StoreTicketCategoryRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $category = TicketCategory::create($request->validated());

        return response()->json([
            'message' => 'Ticket category created successfully',
            'category' => new TicketCategoryResource($category),
        ], 201);
    }

    // Mostrar categoría individual
    public function show(Request $request, TicketCategory $ticketCategory)
    {
        if (! $ticketCategory->is_active && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'category' => new TicketCategoryResource($ticketCategory),
        ]);
    }

    // Actualizar categoría
    public function update(UpdateTicketCategoryRequest $request, TicketCategory $ticketCategory)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $ticketCategory->update($request->validated());

        return response()->json([
            'message' => 'Ticket category updated successfully',
            'category' => new TicketCategoryResource($ticketCategory),
        ]);
    }

    // Eliminar categoría
    public function destroy(Request $request, TicketCategory $ticketCategory)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $ticketCategory->delete();

        return response()->json([
            'message' => 'Ticket category deleted successfully',
        ]);
    }
}