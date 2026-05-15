<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\TripLogRequest;
use App\Models\Fleet\FleetTripLog;
use Illuminate\Http\Request;

class FleetTripController extends Controller
{
    public function index(Request $request)
    {
        $trips = FleetTripLog::query()
            ->with(['vehicle', 'driver'])
            ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
            ->when($request->from, fn($q, $v) => $q->whereDate('trip_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('trip_date', '<=', $v))
            ->latest('trip_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.trips.index', compact('trips'));
    }

    public function create() { $record = new FleetTripLog(); return view('admin.fleet.trips.create', compact('record')); }

    public function store(TripLogRequest $request)
    {
        $data = $request->validated();
        $data['distance'] = $data['end_odometer'] - $data['start_odometer'];
        FleetTripLog::create($data);
        return redirect()->route('admin.fleet.trips.index')->with('success', 'Trip log created.');
    }

    public function show(FleetTripLog $trip) { $record = $trip; return view('admin.fleet.trips.show', compact('record')); }
    public function edit(FleetTripLog $trip) { $record = $trip; return view('admin.fleet.trips.edit', compact('record')); }

    public function update(TripLogRequest $request, FleetTripLog $trip)
    {
        $data = $request->validated();
        $data['distance'] = $data['end_odometer'] - $data['start_odometer'];
        $trip->update($data);
        return redirect()->route('admin.fleet.trips.index')->with('success', 'Trip log updated.');
    }

    public function destroy(FleetTripLog $trip)
    {
        $trip->delete();
        return back()->with('success', 'Trip log deleted.');
    }
}
