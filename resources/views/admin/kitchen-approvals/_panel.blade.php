<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Pending Purchase Orders</span>
        <span class="badge bg-warning text-dark">{{ $pendingPos->count() }}</span>
    </div>
    <div class="card-body">
        @forelse($pendingPos as $po)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap mb-2">
                    <div>
                        <strong>{{ $po->temp_number }}</strong>
                        <span class="badge bg-warning text-dark ms-2">PENDING</span>
                    </div>
                    <div class="text-muted small">
                        {{ $po->staff->name ?? '—' }} &middot;
                        {{ $po->vendor->name ?? '—' }} &middot;
                        {{ optional($po->po_date)->format('d-M-Y') }}
                    </div>
                </div>

                @if($po->remarks)
                    <div class="small text-muted mb-2">Remarks: {{ $po->remarks }}</div>
                @endif

                <form method="POST" action="{{ route('admin.kitchen-approvals.po.approve', $po->id) }}">
                    @csrf
                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end" style="width:120px">Qty</th>
                                <th style="width:160px">Rate (optional)</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($po->lines as $line)
                            <tr>
                                <td>
                                    {{ $line->item->name ?? '—' }}
                                    <span class="text-muted small">{{ $line->item->sku ?? '' }}</span>
                                </td>
                                <td class="text-end">
                                    {{ rtrim(rtrim(number_format((float) $line->qty_ordered, 3, '.', ''), '0'), '.') }}
                                    <span class="text-muted small">{{ $line->item->uom ?? '' }}</span>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                           name="rates[{{ $line->item_id }}]" placeholder="—">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-sm">Approve PO</button>
                </form>

                <form method="POST" action="{{ route('admin.kitchen-approvals.po.reject', $po->id) }}" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-8">
                        <input name="reason" class="form-control form-control-sm" placeholder="Rejection reason" required>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-danger btn-sm">Reject</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="text-center text-muted py-3">No pending purchase orders.</div>
        @endforelse
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Pending Goods Receipts</span>
        <span class="badge bg-warning text-dark">{{ $pendingGrns->count() }}</span>
    </div>
    <div class="card-body">
        @forelse($pendingGrns as $grn)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap mb-2">
                    <div>
                        <strong>{{ $grn->temp_number }}</strong>
                        <span class="badge bg-warning text-dark ms-2">PENDING</span>
                    </div>
                    <div class="text-muted small">
                        {{ $grn->staff->name ?? '—' }} &middot;
                        PO: {{ $grn->kitchenPo->temp_number ?? '—' }} &middot;
                        {{ optional($grn->received_date)->format('d-M-Y') }}
                    </div>
                </div>

                @if(($grn->kitchenPo->status ?? '') === 'PENDING')
                    <div class="alert alert-warning py-2 small">
                        Approve the purchase order first, then this GRN.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.kitchen-approvals.grn.approve', $grn->id) }}">
                    @csrf
                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr>
                                <th style="width:90px">Photo</th>
                                <th>Item</th>
                                <th class="text-end" style="width:120px">Received</th>
                                <th style="width:160px">Unit cost</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($grn->lines as $line)
                            <tr>
                                <td>
                                    @if($line->image_path)
                                        <a href="{{ route('admin.kitchen-approvals.line-image', $line->id) }}" target="_blank">
                                            <img src="{{ route('admin.kitchen-approvals.line-image', $line->id) }}"
                                                 alt="photo" class="rounded border"
                                                 style="width:64px;height:64px;object-fit:cover">
                                        </a>
                                    @else
                                        <span class="text-muted small">none</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $line->item->name ?? '—' }}
                                    <span class="text-muted small">{{ $line->item->sku ?? '' }}</span>
                                </td>
                                <td class="text-end">
                                    {{ rtrim(rtrim(number_format((float) $line->qty_received, 3, '.', ''), '0'), '.') }}
                                    <span class="text-muted small">{{ $line->item->uom ?? '' }}</span>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm"
                                           name="costs[{{ $line->item_id }}]" required>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-success btn-sm"
                        @disabled(($grn->kitchenPo->status ?? '') === 'PENDING')>Approve GRN &amp; post stock</button>
                </form>

                <form method="POST" action="{{ route('admin.kitchen-approvals.grn.reject', $grn->id) }}" class="row g-2 mt-2">
                    @csrf
                    <div class="col-md-8">
                        <input name="reason" class="form-control form-control-sm" placeholder="Rejection reason" required>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-danger btn-sm">Reject</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="text-center text-muted py-3">No pending goods receipts.</div>
        @endforelse
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Recently Reviewed</div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Staff</th>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Reviewed</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
            @forelse($history as $row)
                <tr>
                    <td>{{ $row->temp_number }}</td>
                    <td>{{ $row->staff->name ?? '—' }}</td>
                    <td>{{ $row->vendor->name ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $row->status === 'APPROVED' ? 'success' : 'danger' }}">
                            {{ $row->status }}
                        </span>
                    </td>
                    <td class="small">{{ optional($row->reviewed_at)->format('d-M-Y H:i') }}</td>
                    <td class="small text-muted">{{ $row->reject_reason ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Nothing reviewed yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
