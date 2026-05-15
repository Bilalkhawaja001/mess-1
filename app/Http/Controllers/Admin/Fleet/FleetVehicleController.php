<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\VehicleRequest;
use App\Models\Fleet\FleetVehicle;
use Illuminate\Http\Request;

class FleetVehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = FleetVehicle::query()
            ->with(['driver'])
            ->when($request->search, fn($q, $v) => $q->where('vehicle_no', 'like', "%{$v}%")->orWhere('registration_no', 'like', "%{$v}%"))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('admin.fleet.vehicles.create', ['vehicle' => new FleetVehicle()]);
    }

    public function store(VehicleRequest $request)
    {
        FleetVehicle::create($request->validated());
        return redirect()->route('admin.fleet.vehicles.index')->with('success', 'Vehicle created.');
    }

    public function show(FleetVehicle $vehicle)
    {
        $vehicle->load(['fuelLogs', 'maintenanceLogs', 'documents', 'trips']);
        return view('admin.fleet.vehicles.show', compact('vehicle'));
    }

    public function edit(FleetVehicle $vehicle)
    {
        return view('admin.fleet.vehicles.edit', compact('vehicle'));
    }

    public function update(VehicleRequest $request, FleetVehicle $vehicle)
    {
        $vehicle->update($request->validated());
        return redirect()->route('admin.fleet.vehicles.index')->with('success', 'Vehicle updated.');
    }

    public function destroy(FleetVehicle $vehicle)
    {
        $vehicle->delete();
        return back()->with('success', 'Vehicle deleted.');
    }
}
