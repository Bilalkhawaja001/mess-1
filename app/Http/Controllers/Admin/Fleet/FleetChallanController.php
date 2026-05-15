<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Fleet\FleetChallan;
use App\Http\Requests\Fleet\ChallanRequest;

class FleetChallanController extends Controller
{
    public function index(Request $request)
    {
        $query = FleetChallan::query()->latest('id');
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                foreach (['challan_no', 'violation_type', 'status'] as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        $records = $query->paginate(20)->withQueryString();
        return view('admin.fleet.challans.index', compact('records'));
    }

    public function create()
    {
        $record = new FleetChallan();
        return view('admin.fleet.challans.create', $this->formData(compact('record')));
    }

    public function store(ChallanRequest $request)
    {
        $challan = DB::transaction(fn () => FleetChallan::create($request->validated()));
        return redirect()->route('admin.fleet.challans.show', $challan)->with('success', 'Challan created.');
    }

    public function show(FleetChallan $challan)
    {
        $record = $challan;
        return view('admin.fleet.challans.show', compact('record'));
    }

    public function edit(FleetChallan $challan)
    {
        $record = $challan;
        return view('admin.fleet.challans.edit', $this->formData(compact('record')));
    }

    public function update(ChallanRequest $request, FleetChallan $challan)
    {
        DB::transaction(fn () => $challan->update($request->validated()));
        return redirect()->route('admin.fleet.challans.show', $challan)->with('success', 'Challan updated.');
    }

    public function destroy(FleetChallan $challan)
    {
        DB::transaction(fn () => $challan->delete());
        return redirect()->route('admin.fleet.challans.index')->with('success', 'Challan deleted.');
    }

    private function formData(array $extra = []): array
    {
        return $extra;
    }
}
