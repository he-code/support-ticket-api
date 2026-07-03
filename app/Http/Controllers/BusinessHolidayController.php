<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessHolidayRequest;
use App\Http\Resources\BusinessHolidayResource;
use App\Models\BusinessHoliday;
use Illuminate\Http\Request;

class BusinessHolidayController extends Controller
{
    public function index()
    {
        return response()->json([
            'business_holidays' => BusinessHolidayResource::collection(
                BusinessHoliday::query()->orderBy('date')->get()
            ),
        ]);
    }

    public function store(StoreBusinessHolidayRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $holiday = BusinessHoliday::create($request->validated());

        return response()->json([
            'message' => 'Business holiday created successfully',
            'business_holiday' => new BusinessHolidayResource($holiday),
        ], 201);
    }

    public function destroy(Request $request, BusinessHoliday $businessHoliday)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $businessHoliday->delete();

        return response()->json(['message' => 'Business holiday deleted successfully']);
    }
}
