<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Http\Resources\WebhookDeliveryResource;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'webhooks' => WebhookResource::collection(Webhook::query()->latest()->get()),
        ]);
    }

    public function store(StoreWebhookRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $webhook = Webhook::create([
            ...$request->validated(),
            'created_by_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Webhook created successfully',
            'webhook' => new WebhookResource($webhook),
        ], 201);
    }

    public function show(Request $request, Webhook $webhook)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'webhook' => new WebhookResource($webhook),
        ]);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $webhook->update($request->validated());

        return response()->json([
            'message' => 'Webhook updated successfully',
            'webhook' => new WebhookResource($webhook),
        ]);
    }

    public function destroy(Request $request, Webhook $webhook)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted successfully']);
    }

    public function deliveries(Request $request, Webhook $webhook)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deliveries = $webhook->deliveries()->latest()->paginate(20);

        return response()->json([
            'deliveries' => WebhookDeliveryResource::collection($deliveries),
            'pagination' => [
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
                'from' => $deliveries->firstItem(),
                'to' => $deliveries->lastItem(),
            ],
        ]);
    }
}
