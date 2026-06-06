<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupportAgentResource;
use App\Models\User;
use Illuminate\Http\Request;

class SupportAgentController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isStaff()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $agents = User::query()
            ->where('role', 'support_agent')
            ->orderBy('name')
            ->get();

        return response()->json([
            'support_agents' => SupportAgentResource::collection($agents),
        ]);
    }
}
