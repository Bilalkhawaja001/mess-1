<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Fleet\FleetIncident;
use App\Http\Requests\Fleet\IncidentRequest;

class FleetIncidentController extends Controller
{
    public function index(Request $request)
    {
        $query = FleetIncident::query()->latest('id');
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                foreach (['incident_number', 'location', 'status'] as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        $records = $query->paginate(20)->withQueryString();
        return view('admin.fleet.incidents.index', compact('records'));
    }

    public function create()
    {
        $record = new FleetIncident();
        return view('admin.fleet.incidents.create', $this->formData(compact('record')));
    }

    public function store(IncidentRequest $request)
    {
        $incident = DB::transaction(fn () => FleetIncident::create($request->validated()));
        return redirect()->route('admin.fleet.incidents.show', $incident)->with('success', 'Incident created.');
    }

    public function show(FleetIncident $incident)
    {
        $record = $incident;
        return view('admin.fleet.incidents.show', compact('record'));
    }

    public function edit(FleetIncident $incident)
    {
        $record = $incident;
        return view('admin.fleet.incidents.edit', $this->formData(compact('record')));
    }

    public function update(IncidentRequest $request, FleetIncident $incident)
    {
        DB::transaction(fn () => $incident->update($request->validated()));
        return redirect()->route('admin.fleet.incidents.show', $incident)->with('success', 'Incident updated.');
    }

    public function destroy(FleetIncident $incident)
    {
        DB::transaction(fn () => $incident->delete());
        return redirect()->route('admin.fleet.incidents.index')->with('success', 'Incident deleted.');
    }

    private function formData(array $extra = []): array
    {
        return $extra;
    }
}
