<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public static function addBusinessMinutes(Carbon $start, int $minutes): Carbon
    {
        if (! self::query()->exists()) {
            return $start->copy()->addMinutes($minutes);
        }

        $cursor = self::nextOpenMoment($start->copy());
        $remaining = $minutes;

        while ($remaining > 0) {
            $schedule = self::scheduleFor($cursor);

            if (! $schedule) {
                $cursor = self::nextOpenMoment($cursor->copy()->addDay()->startOfDay());

                continue;
            }

            $closeAt = $cursor->copy()->setTimeFromTimeString($schedule->closes_at);
            $available = max(0, $cursor->diffInMinutes($closeAt, false));

            if ($available >= $remaining) {
                return $cursor->addMinutes($remaining);
            }

            $remaining -= $available;
            $cursor = self::nextOpenMoment($cursor->copy()->addDay()->startOfDay());
        }

        return $cursor;
    }

    private static function nextOpenMoment(Carbon $moment): Carbon
    {
        for ($i = 0; $i < 14; $i++) {
            if (BusinessHoliday::query()->whereDate('date', $moment->toDateString())->exists()) {
                $moment = $moment->addDay()->startOfDay();

                continue;
            }

            $schedule = self::scheduleFor($moment);

            if (! $schedule) {
                $moment = $moment->addDay()->startOfDay();

                continue;
            }

            $openAt = $moment->copy()->setTimeFromTimeString($schedule->opens_at);
            $closeAt = $moment->copy()->setTimeFromTimeString($schedule->closes_at);

            if ($moment->lt($openAt)) {
                return $openAt;
            }

            if ($moment->lt($closeAt)) {
                return $moment;
            }

            $moment = $moment->addDay()->startOfDay();
        }

        return $moment;
    }

    private static function scheduleFor(Carbon $moment): ?self
    {
        return self::query()
            ->where('day_of_week', $moment->dayOfWeek)
            ->where('is_open', true)
            ->whereNotNull('opens_at')
            ->whereNotNull('closes_at')
            ->first();
    }
}
