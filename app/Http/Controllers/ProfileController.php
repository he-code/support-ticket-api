<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // Mostrar información del usuario autenticado
    public function me(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    // Actualizar nombre y correo del usuario autenticado
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    // Cambiar contraseña del usuario autenticado
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // Validamos manualmente la contraseña actual para evitar problemas de guard en API
        if (! Hash::check($request->validated()['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => $request->validated()['password'],
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }
}