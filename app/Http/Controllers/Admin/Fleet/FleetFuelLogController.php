<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\FuelLogRequest;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetVehicle;
use App\Services\Fleet\FleetFuelCalculator;
use App\Services\Fleet\FleetExpensePoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FleetFuelLogController extends Controller
{
    public function __construct(
        private FleetFuelCalculator $calculator,
        private FleetExpensePoster $expensePoster
    ) {}

    public function index(Request $request)
    {
        $fuelLogs = FleetFuelLog::query()
            ->with(['vehicle', 'driver'])
            ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
            ->when($request->from, fn($q, $v) => $q->whereDate('fuel_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('fuel_date', '<=', $v))
            ->latest('fuel_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.fuel.index', compact('fuelLogs'));
    }

    public function create()
    {
        return view('admin.fleet.fuel.create');
    }

    public function store(FuelLogRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $this->calculator->prepareFuelLog($request->validated());
            $log = FleetFuelLog::create($data);
            FleetVehicle::whereKey($log->vehicle_id)->update(['current_odometer' => $log->odometer_reading]);
            $this->expensePoster->postFuelExpense($log);
        });

        return redirect()->route('admin.fleet.fuel.index')->with('success', 'Fuel log posted.');
    }

    public function show(FleetFuelLog $fuelLog) { return view('admin.fleet.fuel.show', ['fuelLog' => $fuelLog->load(['vehicle','driver'])]); }
    public function edit(FleetFuelLog $fuelLog) { return view('admin.fleet.fuel.edit', ['fuelLog' => $fuelLog]); }

    public function update(FuelLogRequest $request, FleetFuelLog $fuelLog)
    {
        DB::transaction(function () use ($request, $fuelLog) {
            $data = $this->calculator->prepareFuelLog($request->validated(), $fuelLog->id);
            $fuelLog->update($data);
            $this->expensePoster->syncFuelExpense($fuelLog->fresh());
        });
        return redirect()->route('admin.fleet.fuel.index')->with('success', 'Fuel log updated.');
    }

    public function destroy(FleetFuelLog $fuelLog)
    {
        DB::transaction(function () use ($fuelLog) {
            $this->expensePoster->removeSourceExpense('fuel', $fuelLog->id);
            $fuelLog->delete();
        });
        return back()->with('success', 'Fuel log deleted.');
    }
}
