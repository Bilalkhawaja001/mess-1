<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Fleet\FleetTyreBattery;
use App\Http\Requests\Fleet\TyreBatteryRequest;

class FleetTyreBatteryController extends Controller
{
    public function index(Request $request)
    {
        $query = FleetTyreBattery::query()->latest('id');
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                foreach (['serial_no', 'brand', 'item_type', 'status'] as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        $records = $query->paginate(20)->withQueryString();
        return view('admin.fleet.tyres_batteries.index', compact('records'));
    }

    public function create()
    {
        $record = new FleetTyreBattery();
        return view('admin.fleet.tyres_batteries.create', $this->formData(compact('record')));
    }

    public function store(TyreBatteryRequest $request)
    {
        $tyreBattery = DB::transaction(fn () => FleetTyreBattery::create($request->validated()));
        return redirect()->route('admin.fleet.tyres-batteries.show', $tyreBattery)->with('success', 'Tyre/Battery created.');
    }

    public function show(FleetTyreBattery $tyreBattery)
    {
        $record = $tyreBattery;
        return view('admin.fleet.tyres_batteries.show', compact('record'));
    }

    public function edit(FleetTyreBattery $tyreBattery)
    {
        $record = $tyreBattery;
        return view('admin.fleet.tyres_batteries.edit', $this->formData(compact('record')));
    }

    public function update(TyreBatteryRequest $request, FleetTyreBattery $tyreBattery)
    {
        DB::transaction(fn () => $tyreBattery->update($request->validated()));
        return redirect()->route('admin.fleet.tyres-batteries.show', $tyreBattery)->with('success', 'Tyre/Battery updated.');
    }

    public function destroy(FleetTyreBattery $tyreBattery)
    {
        DB::transaction(fn () => $tyreBattery->delete());
        return redirect()->route('admin.fleet.tyres-batteries.index')->with('success', 'Tyre/Battery deleted.');
    }

    private function formData(array $extra = []): array
    {
        return $extra;
    }
}
