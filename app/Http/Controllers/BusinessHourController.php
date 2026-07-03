<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessHourRequest;
use App\Http\Resources\BusinessHourResource;
use App\Models\BusinessHour;
use Illuminate\Http\Request;

class BusinessHourController extends Controller
{
    public function index()
    {
        return response()->json([
            'business_hours' => BusinessHourResource::collection(
                BusinessHour::query()->orderBy('day_of_week')->get()
            ),
        ]);
    }

    public function store(StoreBusinessHourRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $businessHour = BusinessHour::updateOrCreate(
            ['day_of_week' => $request->validated()['day_of_week']],
            $request->validated()
        );

        return response()->json([
            'message' => 'Business hour saved successfully',
            'business_hour' => new BusinessHourResource($businessHour),
        ]);
    }

    public function destroy(Request $request, BusinessHour $businessHour)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $businessHour->delete();

        return response()->json(['message' => 'Business hour deleted successfully']);
    }
}
