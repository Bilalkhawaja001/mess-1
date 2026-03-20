<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rates\StoreRatePolicyRequest;
use App\Models\RatePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RateController extends Controller
{
    public function index(): View
    {
        $rows = RatePolicy::query()->orderBy('rate_type')->orderByDesc('effective_from')->get();
        return view('admin.rates.index', compact('rows'));
    }

    public function store(StoreRatePolicyRequest $request): RedirectResponse
    {
        $rateType = strtoupper((string)$request->input('rate_type'));
        $from = $request->input('effective_from');
        $to = $request->input('effective_to');

        $overlap = RatePolicy::query()->where('rate_type', $rateType)
            ->where(function($q) use ($from, $to) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from);
            })
            ->whereDate('effective_from', '<=', $to ?: '9999-12-31')
            ->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'Overlapping rate window for same rate type.');
        }

        RatePolicy::query()->create([
            'rate_type'=>$rateType,
            'value'=>$request->input('value'),
            'effective_from'=>$from,
            'effective_to'=>$to ?: null,
            'is_active'=>$request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.rates.index')->with('success', 'Rate policy added (pending approval).');
    }

    public function toggleApprove(RatePolicy $rate): RedirectResponse
    {
        if ($rate->approved_at) {
            $rate->approved_at = null;
            $rate->approved_by_user_id = null;
        } else {
            $rate->approved_at = now();
            $rate->approved_by_user_id = Auth::id();
        }
        $rate->save();

        return back()->with('success', 'Rate approval status updated.');
    }

    public function toggleActive(RatePolicy $rate): RedirectResponse
    {
        $rate->is_active = ! $rate->is_active;
        $rate->save();
        return back()->with('success', 'Rate active status updated.');
    }

    public function toggleLock(RatePolicy $rate): RedirectResponse
    {
        // Flask parity: lock/unlock maps to active flag in current schema.
        $rate->is_active = ! $rate->is_active;
        $rate->save();

        return back()->with('success', 'Rate lock status updated.');
    }

    public function update(Request $request, RatePolicy $rate): RedirectResponse
    {
        $data = $request->validate([
            'value' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        $rate->update($data + ['is_active' => (bool) ($data['is_active'] ?? $rate->is_active)]);

        return back()->with('success', 'Rate updated.');
    }

    public function destroy(RatePolicy $rate): RedirectResponse
    {
        $rate->delete();

        return back()->with('success', 'Rate deleted.');
    }
}
