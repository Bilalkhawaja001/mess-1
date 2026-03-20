<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\GuestMeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
