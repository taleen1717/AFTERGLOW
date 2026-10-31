<?php

namespace App\View\Components;

use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class DailyGlow extends Component
{
    /**
     * Feature one existing quote per Saudi calendar day.
     * Cache the selected quote ID so adding a new quote during the day
     * does not unexpectedly change today's selection.
     */
    public function render(): View
    {
        $today = now('Asia/Riyadh');
        $todayKey = 'afterglow:daily-glow:v2:' . $today->toDateString();
        $yesterdayKey = 'afterglow:daily-glow:v2:' . $today->copy()->subDay()->toDateString();

        $ids = Quote::query()->orderBy('id')->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $dailyQuote = null;

        if (count($ids) > 0) {
            $selectedId = (int) (Cache::get($todayKey) ?: 0);

            // The cached quote may have been deleted; select again only then.
            if (!in_array($selectedId, $ids, true)) {
                $yesterdayId = (int) (Cache::get($yesterdayKey) ?: 0);
                $dayNumber = (int) floor($today->copy()->startOfDay()->timestamp / 86400);
                $count = count($ids);

                if ($count === 1) {
                    $selectedId = $ids[0];
                } elseif (in_array($yesterdayId, $ids, true)) {
                    // Always advance to a different quote from yesterday.
                    $position = array_search($yesterdayId, $ids, true);
                    $selectedId = $ids[($position + 1) % $count];
                } else {
                    // First day after installing this update: avoid the
                    // quote the previous algorithm may already be showing.
                    $oldSelection = $ids[$dayNumber % $count];
                    $alternatives = array_values(array_filter(
                        $ids,
                        static fn (int $id): bool => $id !== $oldSelection
                    ));
                    $selectedId = $alternatives[$dayNumber % count($alternatives)];
                }

                Cache::put($todayKey, $selectedId, $today->copy()->addDays(7));
            }

            $dailyQuote = Quote::query()->with('user')->find($selectedId);
        }

        return view('components.daily-glow', [
            'dailyQuote' => $dailyQuote,
            'todayLabel' => $today->format('M d, Y'),
        ]);
    }
}
