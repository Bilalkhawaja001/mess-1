<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\DriverRequest;
use App\Models\Fleet\FleetDriver;
use Illuminate\Http\Request;

class FleetDriverController extends Controller
{
    public function index(Request $request)
    {
        $drivers = FleetDriver::query()
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('employee_code', 'like', "%{$v}%"))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.drivers.index', compact('drivers'));
    }

    public function create() { return view('admin.fleet.drivers.create', ['driver' => new FleetDriver()]); }

    public function store(DriverRequest $request)
    {
        FleetDriver::create($request->validated());
        return redirect()->route('admin.fleet.drivers.index')->with('success', 'Driver created.');
    }

    public function show(FleetDriver $driver)
    {
        $driver->load(['vehicle', 'fuelLogs', 'trips']);
        return view('admin.fleet.drivers.show', compact('driver'));
    }

    public function edit(FleetDriver $driver) { return view('admin.fleet.drivers.edit', compact('driver')); }

    public function update(DriverRequest $request, FleetDriver $driver)
    {
        $driver->update($request->validated());
        return redirect()->route('admin.fleet.drivers.index')->with('success', 'Driver updated.');
    }

    public function destroy(FleetDriver $driver)
    {
        $driver->delete();
        return back()->with('success', 'Driver deleted.');
    }
}
