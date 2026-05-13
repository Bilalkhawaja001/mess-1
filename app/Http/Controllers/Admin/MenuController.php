<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMenuHistory;
use App\Models\Menu;
use App\Models\Mess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
 {
 $rows = Menu::query()
 ->with(['creator', 'approver', 'mess'])
 ->when($request->filled('from'), fn ($q) => $q->whereDate('menu_date', '>=', $request->input('from')))
 ->when($request->filled('to'), fn ($q) => $q->whereDate('menu_date', '<=', $request->input('to')))
 ->when($request->filled('meal_type'), fn ($q) => $q->where('meal_type', $request->input('meal_type')))
 ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
 ->when($request->filled('mess_id'), fn ($q) => $q->where('mess_id', (int) $request->input('mess_id')))
 ->orderByDesc('menu_date')
 ->orderByDesc('id')
 ->paginate(50)
 ->withQueryString();

 $messes = Mess::query()->where('is_active', 1)->orderBy('name')->get();

 return view('admin.menu.index', compact('rows', 'messes'));
 }

    public function store(Request $request): RedirectResponse
 {
 $payload = $request->validate([
 'menu_date' => ['required', 'date'],
 'mess_id' => ['required', 'integer', 'exists:messes,id'],
 'meal_type' => ['required', 'string'],
 'title' => ['nullable', 'string', 'max:255'],
 'description' => ['nullable', 'string'],
 'items_text' => ['required', 'string'],
 'remarks' => ['nullable', 'string'],
 ]);

 $payload['title'] = trim((string) ($payload['title'] ?? ''));

 Menu::query()->create($payload + [
 'status' => Menu::STATUS_DRAFT,
 'created_by' => Auth::id(),
 ]);

 return back()->with('success', 'Menu created.');
 }

    public function update(Request $request, Menu $menu): RedirectResponse
 {
 $payload = $request->validate([
 'menu_date' => ['required', 'date'],
 'mess_id' => ['required', 'integer', 'exists:messes,id'],
 'meal_type' => ['required', 'string'],
 'title' => ['nullable', 'string', 'max:255'],
 'description' => ['nullable', 'string'],
 'items_text' => ['required', 'string'],
 'remarks' => ['nullable', 'string'],
 ]);

 $payload['title'] = trim((string) ($payload['title'] ?? ''));

 $this->saveHistory($menu, 'UPDATE');
 $menu->update($payload);

 return back()->with('success', 'Menu updated.');
 }

    public function approve(Menu $menu): RedirectResponse
    {
        $this->saveHistory($menu, 'APPROVE');
        $menu->update([
            'status' => Menu::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Menu approved.');
    }

    public function inactive(Menu $menu): RedirectResponse
    {
        $this->saveHistory($menu, 'INACTIVE');
        $menu->update([
            'status' => Menu::STATUS_INACTIVE,
        ]);

        return back()->with('success', 'Menu marked inactive.');
    }

    public function export(Request $request)
 {
 $rows = Menu::query()
 ->with(['creator', 'approver', 'mess'])
 ->when($request->filled('from'), fn ($q) => $q->whereDate('menu_date', '>=', $request->input('from')))
 ->when($request->filled('to'), fn ($q) => $q->whereDate('menu_date', '<=', $request->input('to')))
 ->when($request->filled('meal_type'), fn ($q) => $q->where('meal_type', $request->input('meal_type')))
 ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
 ->when($request->filled('mess_id'), fn ($q) => $q->where('mess_id', (int) $request->input('mess_id')))
 ->orderByDesc('menu_date')
 ->get();

 return response()->streamDownload(function () use ($rows) {
 $out = fopen('php://output', 'w');
 fputcsv($out, ['Date', 'Mess Name', 'Mess Code', 'Meal Type', 'Title', 'Items', 'Status', 'Created By', 'Approved By', 'Approved At']);
 foreach ($rows as $row) {
 fputcsv($out, [
 $row->menu_date,
 $row->mess?->name,
 $row->mess?->code,
 $row->meal_type,
 $row->title,
 $row->items_text,
 $row->status,
 $row->creator?->name,
 $row->approver?->name,
 optional($row->approved_at)->format('Y-m-d H:i'),
 ]);
 }
 fclose($out);
 }, 'menu.csv', ['Content-Type' => 'text/csv']);
 }

    private function saveHistory(Menu $menu, string $action): void
    {
        DailyMenuHistory::query()->create([
            'daily_menu_id' => $menu->id,
            'action' => $action,
            'changed_by' => Auth::id(),
            'snapshot' => $menu->fresh()?->toArray() ?? $menu->toArray(),
        ]);
    }
}
