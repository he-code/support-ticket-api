<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAutomationRuleRequest;
use App\Http\Requests\UpdateAutomationRuleRequest;
use App\Http\Resources\AutomationRuleResource;
use App\Models\AutomationRule;
use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = AutomationRule::query()
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        return response()->json([
            'automation_rules' => AutomationRuleResource::collection($rules),
        ]);
    }

    public function store(StoreAutomationRuleRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rule = AutomationRule::create($request->validated());

        return response()->json([
            'message' => 'Automation rule created successfully',
            'automation_rule' => new AutomationRuleResource($rule),
        ], 201);
    }

    public function show(Request $request, AutomationRule $automationRule)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'automation_rule' => new AutomationRuleResource($automationRule),
        ]);
    }

    public function update(UpdateAutomationRuleRequest $request, AutomationRule $automationRule)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $automationRule->update($request->validated());

        return response()->json([
            'message' => 'Automation rule updated successfully',
            'automation_rule' => new AutomationRuleResource($automationRule),
        ]);
    }

    public function destroy(Request $request, AutomationRule $automationRule)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $automationRule->delete();

        return response()->json([
            'message' => 'Automation rule deleted successfully',
        ]);
    }
}
