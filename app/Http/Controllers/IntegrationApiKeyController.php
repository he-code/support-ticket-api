<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationApiKeyRequest;
use App\Http\Resources\IntegrationApiKeyResource;
use App\Models\IntegrationApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationApiKeyController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'api_keys' => IntegrationApiKeyResource::collection(
                IntegrationApiKey::query()->latest()->get()
            ),
        ]);
    }

    public function store(StoreIntegrationApiKeyRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plainToken = 'stk_'.Str::random(48);

        $apiKey = IntegrationApiKey::create([
            ...$request->validated(),
            'created_by_id' => $request->user()->id,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'message' => 'API key created successfully',
            'plain_token' => $plainToken,
            'api_key' => new IntegrationApiKeyResource($apiKey),
        ], 201);
    }

    public function destroy(Request $request, IntegrationApiKey $apiKey)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $apiKey->delete();

        return response()->json(['message' => 'API key deleted successfully']);
    }
}
