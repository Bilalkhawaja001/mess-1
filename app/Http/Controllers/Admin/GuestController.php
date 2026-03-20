<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestMeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    public function index()
    {
        $guests = Guest::latest()->get();
        $meals = GuestMeal::latest('meal_date')->limit(100)->get();
        $summary = (float) GuestMeal::sum('amount');

        return view('admin.guests.index', compact('guests', 'meals', 'summary'));
    }

    public function storeGuest(Request $r): RedirectResponse
    {
        Guest::create($r->validate([
            'name' => 'required',
            'contact' => 'nullable',
            'department' => 'nullable',
        ]));

        return back()->with('success', 'Guest created');
    }

    public function updateGuest(Request $r, Guest $guest): RedirectResponse
    {
        $guest->update($r->validate([
            'name' => 'required',
            'contact' => 'nullable',
            'department' => 'nullable',
            'is_active' => 'nullable|boolean',
        ]));

        return back()->with('success', 'Guest updated');
    }

    public function deleteGuest(Guest $guest): RedirectResponse
    {
        $guest->delete();

        return back()->with('success', 'Guest deleted');
    }

    public function storeMeal(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'guest_id' => 'required|exists:guests,id',
            'meal_date' => 'required|date',
            'meal_type' => 'required',
            'quantity' => 'required|integer|min:1',
            'rate' => 'required|numeric|min:0',
        ]);
        $d['amount'] = round($d['quantity'] * $d['rate'], 2);
        GuestMeal::create($d);

        return back()->with('success', 'Guest meal added');
    }

    public function updateMeal(Request $r, GuestMeal $meal): RedirectResponse
    {
        $d = $r->validate([
            'guest_id' => 'required|exists:guests,id',
            'meal_date' => 'required|date',
            'meal_type' => 'required',
            'quantity' => 'required|integer|min:1',
            'rate' => 'required|numeric|min:0',
        ]);
        $d['amount'] = round($d['quantity'] * $d['rate'], 2);
        $meal->update($d);

        return back()->with('success', 'Guest meal updated');
    }

    public function deleteMeal(GuestMeal $meal): RedirectResponse
    {
        $meal->delete();

        return back()->with('success', 'Guest meal deleted');
    }

    public function approveMeal(GuestMeal $meal): RedirectResponse
    {
        // Approval endpoint for workflow parity (no dedicated approval columns yet).
        $meal->touch();

        return back()->with('success', 'Guest meal approved.');
    }

    public function exportMeals(Request $request): StreamedResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $q = GuestMeal::query()->with('guest')->orderBy('meal_date');
        if ($from) {
            $q->whereDate('meal_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('meal_date', '<=', $to);
        }
        $rows = $q->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['meal_date', 'guest_name', 'meal_type', 'quantity', 'rate', 'amount']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->meal_date,
                    $row->guest?->name,
                    $row->meal_type,
                    $row->quantity,
                    $row->rate,
                    $row->amount,
                ]);
            }
            fclose($out);
        }, 'guest_meals_export.csv', ['Content-Type' => 'text/csv']);
    }

    public function importGuests(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $counts = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($this->csvRows($request) as $row) {
            $payload = [
                'name' => trim((string) ($row['name'] ?? '')),
                'contact' => $this->nullableText($row['contact'] ?? null),
                'department' => $this->nullableText($row['department'] ?? null),
                'is_active' => $this->toBoolean($row['is_active'] ?? true),
            ];

            $validator = Validator::make($payload, [
                'name' => 'required|string|max:255',
                'contact' => 'nullable|string|max:80',
                'department' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            $existing = Guest::query()->where('name', $payload['name'])->where('contact', $payload['contact'])->first();
            if ($existing) {
                $existing->update($payload);
                $counts['updated']++;
            } else {
                Guest::query()->create($payload);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Guests import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    public function importMeals(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $counts = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($this->csvRows($request) as $row) {
            $payload = [
                'guest_id' => $row['guest_id'] ?? null,
                'meal_date' => $this->nullableText($row['meal_date'] ?? null),
                'meal_type' => trim((string) ($row['meal_type'] ?? '')),
                'quantity' => $row['quantity'] ?? null,
                'rate' => $row['rate'] ?? null,
            ];

            $validator = Validator::make($payload, [
                'guest_id' => 'required|exists:guests,id',
                'meal_date' => 'required|date',
                'meal_type' => 'required|string|max:30',
                'quantity' => 'required|integer|min:1',
                'rate' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            $payload['amount'] = round((float) $payload['quantity'] * (float) $payload['rate'], 2);
            $existing = GuestMeal::query()
                ->where('guest_id', $payload['guest_id'])
                ->whereDate('meal_date', $payload['meal_date'])
                ->where('meal_type', $payload['meal_type'])
                ->first();

            if ($existing) {
                $existing->update($payload);
                $counts['updated']++;
            } else {
                GuestMeal::query()->create($payload);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Guest meals import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    private function csvRows(Request $request): array
    {
        $rows = [];
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($file) ?: [];
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        while (($line = fgetcsv($file)) !== false) {
            if (! array_filter($line, fn ($v) => trim((string) $v) !== '')) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }

        fclose($file);

        return $rows;
    }

    private function nullableText(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v === '' ? null : $v;
    }

    private function toBoolean(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y'], true);
    }
}
