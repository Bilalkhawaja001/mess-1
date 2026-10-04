<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Purchase History</span>

        <span class="text-muted small">
            Last 12 cycles &middot; click total to expand
        </span>
    </div>

    <div class="card-body table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Cycle</th>
                    <th>Period</th>
                    <th>Month</th>
                    <th class="text-end">Total Purchase</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
            @foreach($historyMonths as $mi => $m)

                {{-- MONTH --}}
                <tr>
                    <td>
                        <button
                            class="btn btn-sm btn-outline-secondary py-0 px-2 ph-toggle"
                            type="button"
                            data-target="ph-month-{{ $mi }}">
                            +
                        </button>
                    </td>

                    <td class="fw-semibold">
                        {{ $m['month_cycle'] }}
                    </td>

                    <td class="small text-muted">
                        {{ \Carbon\Carbon::parse($m['from'])->format('d M Y') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($m['to'])->format('d M Y') }}
                    </td>

                    <td>
                        {{ \Carbon\Carbon::createFromFormat('!Y-m', $m['month_cycle'])->format('F Y') }}
                    </td>

                    <td class="text-end">
                        <button
                            type="button"
                            data-target="ph-month-{{ $mi }}"
                            class="btn btn-link p-0 fw-bold text-decoration-none ph-toggle">
                            Rs. {{ number_format($m['total'], 2) }}
                        </button>
                    </td>

                    <td class="text-end text-nowrap">
                        <a
                            href="#"
                            target="_blank"
                            class="btn btn-sm btn-outline-secondary">
                            View
                        </a>

                        <a
                            href="#"
                            class="btn btn-sm btn-outline-primary">
                            Download
                        </a>
                    </td>
                </tr>

                {{-- MONTH EXPANDED --}}
                <tr
                    id="ph-month-{{ $mi }}"
                    style="display:none">

                    <td></td>

                    <td colspan="5" class="bg-light p-3">

                        @forelse($m['vendors'] as $vi => $v)

                            {{-- VENDOR --}}
                            <div
                                class="d-flex justify-content-between align-items-center py-2 border-bottom gap-3">

                                <div class="d-flex align-items-center gap-2">
                                    <button
                                        class="btn btn-sm btn-outline-secondary py-0 px-2 ph-toggle"
                                        type="button"
                                        data-target="ph-v-{{ $mi }}-{{ $vi }}">
                                        +
                                    </button>

                                    <strong>
                                        {{ $v['vendor_name'] }}
                                    </strong>

                                    <span class="text-muted small">
                                        {{ count($v['dates']) }} purchasing day(s)
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-2 text-nowrap">

                                    <button
                                        type="button"
                                        data-target="ph-v-{{ $mi }}-{{ $vi }}"
                                        class="btn btn-link p-0 fw-bold text-decoration-none ph-toggle">
                                        Rs. {{ number_format($v['total'], 2) }}
                                    </button>

                                    <a
                                        href="#"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-secondary">
                                        View
                                    </a>

                                    <a
                                        href="#"
                                        class="btn btn-sm btn-outline-primary">
                                        Download
                                    </a>
                                </div>
                            </div>

                            {{-- VENDOR DETAIL --}}
                            <div
                                id="ph-v-{{ $mi }}-{{ $vi }}"
                                style="display:none"
                                class="py-3 ps-md-4">

                                @foreach($v['dates'] as $date => $items)

                                    <div class="fw-bold mt-2 mb-1">
                                        {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered bg-white mb-3">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-end">Qty</th>
                                                    <th class="text-end">Avg Rate</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                            @foreach($items as $it)
                                                @php
                                                    $avgRate =
                                                        (float)$it['qty'] != 0
                                                        ? (float)$it['amount'] / (float)$it['qty']
                                                        : 0;
                                                @endphp

                                                <tr>
                                                    <td>
                                                        {{ $it['item'] }}

                                                        <span class="text-muted small">
                                                            {{ $it['sku'] }}
                                                        </span>
                                                    </td>

                                                    <td class="text-end">
                                                        {{ rtrim(rtrim(number_format($it['qty'], 3, '.', ''), '0'), '.') }}
                                                        <span class="text-muted small">
                                                            {{ $it['uom'] }}
                                                        </span>
                                                    </td>

                                                    <td class="text-end">
                                                        {{ number_format($avgRate, 2) }}
                                                    </td>

                                                    <td class="text-end fw-semibold">
                                                        {{ number_format($it['amount'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                @endforeach
                            </div>

                        @empty
                            <div class="text-muted py-3">
                                No purchases in this cycle.
                            </div>
                        @endforelse

                    </td>
                </tr>

            @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.ph-toggle');

    if (!btn) {
        return;
    }

    const target = document.getElementById(
        btn.dataset.target
    );

    if (!target) {
        return;
    }

    const opening =
        target.style.display === 'none'
        || target.style.display === '';

    target.style.display =
        opening
            ? (
                target.tagName === 'TR'
                    ? 'table-row'
                    : 'block'
            )
            : 'none';

    document
        .querySelectorAll(
            '.ph-toggle[data-target="'
            + btn.dataset.target
            + '"]'
        )
        .forEach(function (other) {
            if (
                other.tagName === 'BUTTON'
                && other.classList.contains('btn-outline-secondary')
            ) {
                other.textContent = opening
                    ? '−'
                    : '+';
            }
        });
});
</script>
@endpush
