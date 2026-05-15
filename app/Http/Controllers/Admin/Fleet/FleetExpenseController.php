<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetExpense;
use Illuminate\Http\Request;

class FleetExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = FleetExpense::query()
            ->with(['vehicle', 'driver'])
            ->when($request->vehicle_id, fn ($query, $vehicleId) => $query->where('vehicle_id', $vehicleId))
            ->when($request->driver_id, fn ($query, $driverId) => $query->where('driver_id', $driverId))
            ->when($request->category, fn ($query, $category) => $query->where('category', $category))
            ->when($request->from, fn ($query, $from) => $query->whereDate('expense_date', '>=', $from))
            ->when($request->to, fn ($query, $to) => $query->whereDate('expense_date', '<=', $to))
            ->latest('expense_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.expenses.index', compact('expenses'));
    }
}
