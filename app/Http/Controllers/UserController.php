<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Listar usuarios del sistema
    public function index(Request $request)
    {
        // Solo el administrador puede gestionar usuarios
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validamos filtros simples para búsqueda y rol
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:user,support_agent,admin',
        ]);

        $query = User::query();

        // Buscar usuario por nombre o correo
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Filtrar usuarios por rol
        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Ordenamos por fecha de creación, más recientes primero
        $users = $query->latest()->paginate(10);

        return response()->json([
            'users' => UserResource::collection($users),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    // Actualizar rol de usuario
    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        // Solo el administrador puede cambiar roles
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Evitamos que un admin se quite su propio rol por accidente
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot change your own role',
            ], 403);
        }

        $user->update([
            'role' => $request->validated()['role'],
        ]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}
