<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomFieldRequest;
use App\Http\Requests\UpdateCustomFieldRequest;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomField::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $request->user()->isAdmin()) {
            $query->where('is_active', true);
        }

        if ($request->filled('category_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('category_id')
                    ->orWhere('category_id', $request->integer('category_id'));
            });
        }

        return response()->json([
            'custom_fields' => CustomFieldResource::collection($query->get()),
        ]);
    }

    public function store(StoreCustomFieldRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $field = CustomField::create($request->validated());

        return response()->json([
            'message' => 'Custom field created successfully',
            'custom_field' => new CustomFieldResource($field),
        ], 201);
    }

    public function show(Request $request, CustomField $customField)
    {
        if (! $customField->is_active && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'custom_field' => new CustomFieldResource($customField),
        ]);
    }

    public function update(UpdateCustomFieldRequest $request, CustomField $customField)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customField->update($request->validated());

        return response()->json([
            'message' => 'Custom field updated successfully',
            'custom_field' => new CustomFieldResource($customField),
        ]);
    }

    public function destroy(Request $request, CustomField $customField)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customField->delete();

        return response()->json(['message' => 'Custom field deleted successfully']);
    }
}
