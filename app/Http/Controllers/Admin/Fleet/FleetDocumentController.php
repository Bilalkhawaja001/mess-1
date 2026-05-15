<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\VehicleDocumentRequest;
use App\Models\Fleet\FleetVehicleDocument;
use App\Services\Fleet\FleetDocumentStatusService;
use Illuminate\Http\Request;

class FleetDocumentController extends Controller
{
    public function __construct(private FleetDocumentStatusService $statusService) {}

    public function index(Request $request)
    {
        $documents = FleetVehicleDocument::query()
            ->with('vehicle')
            ->when($request->vehicle_id, fn($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest('expiry_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fleet.documents.index', compact('documents'));
    }

    public function create() { $record = new FleetVehicleDocument(); return view('admin.fleet.documents.create', compact('record')); }

    public function store(VehicleDocumentRequest $request)
    {
        $data = $request->validated();
        $data['status'] = $this->statusService->status($data['expiry_date']);
        FleetVehicleDocument::create($data);
        return redirect()->route('admin.fleet.documents.index')->with('success', 'Document saved.');
    }

    public function show(FleetVehicleDocument $document) { $record = $document; return view('admin.fleet.documents.show', compact('record')); }
    public function edit(FleetVehicleDocument $document) { $record = $document; return view('admin.fleet.documents.edit', compact('record')); }

    public function update(VehicleDocumentRequest $request, FleetVehicleDocument $document)
    {
        $data = $request->validated();
        $data['status'] = $this->statusService->status($data['expiry_date']);
        $document->update($data);
        return redirect()->route('admin.fleet.documents.index')->with('success', 'Document updated.');
    }

    public function destroy(FleetVehicleDocument $document)
    {
        $document->delete();
        return back()->with('success', 'Document deleted.');
    }
}
