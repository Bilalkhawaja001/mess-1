<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\MaintenanceRequest;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Services\Fleet\FleetExpensePoster;
use App\Services\Fleet\FleetMaintenanceScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FleetMaintenanceController extends Controller
{
    public function __construct(
        private FleetMaintenanceScheduler $scheduler,
        private FleetExpensePoster $expensePoster
    ) {}

    public function index(Request $request)
    {
        $logs = FleetMaintenanceLog::query()
            ->with('vehicle')
            ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->from, fn($q, $v) => $q->whereDate('maintenance_date', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('maintenance_date', '<=', $v))
            ->latest('maintenance_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.maintenance.index', compact('logs'));
    }

    public function create() { return view('admin.fleet.maintenance.create'); }

    public function store(MaintenanceRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $this->scheduler->prepareMaintenance($request->validated());
            $log = FleetMaintenanceLog::create($data);
            $this->expensePoster->postMaintenanceExpense($log);
        });
        return redirect()->route('admin.fleet.maintenance.index')->with('success', 'Maintenance log posted.');
    }

    public function show(FleetMaintenanceLog $maintenance) { return view('admin.fleet.maintenance.show', compact('maintenance')); }
    public function edit(FleetMaintenanceLog $maintenance) { return view('admin.fleet.maintenance.edit', compact('maintenance')); }

    public function update(MaintenanceRequest $request, FleetMaintenanceLog $maintenance)
    {
        DB::transaction(function () use ($request, $maintenance) {
            $maintenance->update($this->scheduler->prepareMaintenance($request->validated(), $maintenance));
            $this->expensePoster->syncMaintenanceExpense($maintenance->fresh());
        });
        return redirect()->route('admin.fleet.maintenance.index')->with('success', 'Maintenance log updated.');
    }

    public function destroy(FleetMaintenanceLog $maintenance)
    {
        DB::transaction(function () use ($maintenance) {
            $this->expensePoster->removeSourceExpense('maintenance', $maintenance->id);
            $maintenance->delete();
        });
        return back()->with('success', 'Maintenance log deleted.');
    }

    public function approve(FleetMaintenanceLog $maintenance)
    {
        abort_unless(in_array($maintenance->status, ['Pending', 'Requested'], true), 422, 'Only pending maintenance can be approved.');
        $maintenance->update(['status' => 'Approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        return back()->with('success', 'Maintenance approved.');
    }

    public function start(FleetMaintenanceLog $maintenance)
    {
        abort_unless($maintenance->status === 'Approved', 422, 'Only approved maintenance can be started.');
        $maintenance->update(['status' => 'In Progress', 'started_at' => now()]);
        $maintenance->vehicle?->update(['status' => 'Under Maintenance']);
        return back()->with('success', 'Maintenance started.');
    }

    public function complete(MaintenanceRequest $request, FleetMaintenanceLog $maintenance)
    {
        abort_unless($maintenance->status === 'In Progress', 422, 'Only in-progress maintenance can be completed.');
        DB::transaction(function () use ($request, $maintenance) {
            $data = $this->scheduler->prepareMaintenance($request->validated(), $maintenance);
            $data['status'] = 'Completed';
            $data['completed_at'] = now();
            $maintenance->update($data);
            $maintenance->vehicle?->update(['status' => 'Active', 'current_odometer' => max((float)$maintenance->vehicle->current_odometer, (float)$maintenance->odometer_reading)]);
            $this->expensePoster->syncMaintenanceExpense($maintenance->fresh());
        });
        return redirect()->route('admin.fleet.maintenance.show', $maintenance)->with('success', 'Maintenance completed.');
    }

    public function cancel(FleetMaintenanceLog $maintenance)
    {
        abort_if($maintenance->status === 'Completed', 422, 'Completed maintenance cannot be cancelled.');
        $maintenance->update(['status' => 'Cancelled', 'cancelled_at' => now()]);
        if ($maintenance->vehicle?->status === 'Under Maintenance') {
            $maintenance->vehicle->update(['status' => 'Active']);
        }
        return back()->with('success', 'Maintenance cancelled.');
    }
}
