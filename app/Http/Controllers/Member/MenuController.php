<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $anchor = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $weekStart = $anchor->copy()->startOfDay();
        $weekEnd = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $rows = Menu::query()
            ->where('status', Menu::STATUS_APPROVED)
            ->whereBetween('menu_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('menu_date')
            ->get();

        $grid = [];
        for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
            $key = $day->toDateString();
            $grid[$key] = [
                'day' => $day->format('l'),
                'date' => $day->format('Y-m-d'),
                'BREAKFAST' => '-',
                'LUNCH' => '-',
                'DINNER' => '-',
                'TEA_OTHER' => '-',
            ];
        }

        foreach ($rows as $row) {
            $key = optional($row->menu_date)->format('Y-m-d');
            if (! isset($grid[$key])) {
                continue;
            }

            $bucket = in_array($row->meal_type, ['TEA', 'OTHER'], true) ? 'TEA_OTHER' : $row->meal_type;
            $value = trim($row->title."\n".$row->items_text);
            $grid[$key][$bucket] = $grid[$key][$bucket] === '-' ? $value : ($grid[$key][$bucket]."\n\n".$value);
        }

        return view('member.mobile.menu', [
            'weekRows' => array_values($grid),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'prevWeek' => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek' => $weekStart->copy()->addWeek()->toDateString(),
        ]);
    }
}
