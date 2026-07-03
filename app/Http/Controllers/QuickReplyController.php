<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuickReplyRequest;
use App\Http\Requests\UpdateQuickReplyRequest;
use App\Http\Resources\QuickReplyResource;
use App\Models\QuickReply;
use Illuminate\Http\Request;

class QuickReplyController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = QuickReply::query()
            ->with('createdBy')
            ->orderBy('title');

        if (! $request->user()->isAdmin()) {
            $query->where('is_active', true);
        }

        return response()->json([
            'quick_replies' => QuickReplyResource::collection($query->get()),
        ]);
    }

    public function store(StoreQuickReplyRequest $request)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reply = QuickReply::create([
            ...$request->validated(),
            'created_by_id' => $request->user()->id,
        ]);

        $reply->load('createdBy');

        return response()->json([
            'message' => 'Quick reply created successfully',
            'quick_reply' => new QuickReplyResource($reply),
        ], 201);
    }

    public function show(Request $request, QuickReply $quickReply)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $quickReply->is_active && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quickReply->load('createdBy');

        return response()->json([
            'quick_reply' => new QuickReplyResource($quickReply),
        ]);
    }

    public function update(UpdateQuickReplyRequest $request, QuickReply $quickReply)
    {
        if (! $request->user()->isAdmin() && $quickReply->created_by_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quickReply->update($request->validated());
        $quickReply->load('createdBy');

        return response()->json([
            'message' => 'Quick reply updated successfully',
            'quick_reply' => new QuickReplyResource($quickReply),
        ]);
    }

    public function destroy(Request $request, QuickReply $quickReply)
    {
        if (! $request->user()->isAdmin() && $quickReply->created_by_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quickReply->delete();

        return response()->json([
            'message' => 'Quick reply deleted successfully',
        ]);
    }
}
