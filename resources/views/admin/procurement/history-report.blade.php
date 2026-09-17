<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>
        Purchase History - {{ $month_label }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f8;
            color: #172033;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 24px auto;
            background: #fff;
            padding: 28px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 2px solid #17375e;
            padding-bottom: 16px;
        }

        h1 {
            margin: 0;
            font-size: 25px;
        }

        h2 {
            margin: 4px 0 0;
            font-size: 15px;
            font-weight: 600;
            color: #657080;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            border: 1px solid #17375e;
            border-radius: 6px;
            padding: 8px 13px;
            text-decoration: none;
            color: #17375e;
            background: white;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-primary {
            color: white;
            background: #17375e;
        }

        .summary {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 20px 0;
        }

        .box {
            border: 1px solid #dce2e8;
            border-radius: 7px;
            padding: 13px;
        }

        .label {
            color: #77808e;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .value {
            font-size: 15px;
            font-weight: 700;
        }

        .total {
            color: #17375e;
            font-size: 20px;
        }

        .vendor {
            margin-top: 24px;
        }

        .vendor-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #eaf2f8;
            border-left: 4px solid #17375e;
            padding: 10px 12px;
            font-weight: 700;
        }

        .date-head {
            margin: 16px 0 6px;
            font-weight: 700;
            color: #34495e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th {
            background: #f2f4f6;
            text-align: left;
            font-size: 12px;
            padding: 8px;
            border: 1px solid #d9dee5;
        }

        td {
            font-size: 12px;
            padding: 8px;
            border: 1px solid #e0e4e9;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: #7a8390;
        }

        .vendor-summary {
            margin-top: 20px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            html, body {
                background: white !important;
                margin: 0;
                padding: 0;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
                background: white;
            }

            .top {
                border-bottom: 1px solid #17375e;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }

            .summary {
                gap: 8px;
                margin: 12px 0;
            }

            .box {
                padding: 8px;
            }

            .no-print {
                display: none !important;
            }

            .vendor {
                break-inside: auto !important;
                page-break-inside: auto !important;
            }

            .vendor-head {
                break-after: avoid-page;
                page-break-after: avoid;
            }

            table {
                break-inside: auto;
                page-break-inside: auto;
            }

            thead {
                display: table-header-group;
            }

            tr, td, th {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
<div class="page">

    <div class="top">
        <div>
            <h1>Admin Mess</h1>
            <h2>Purchase History</h2>
        </div>

        <div class="actions no-print">
            <a
                href="{{ route('admin.procurement.index', ['tab' => 'history']) }}"
                class="btn">
                Back
            </a>

            <a
                href="{{ route('admin.purchase-history.pdf', array_filter([
                    'monthCycle' => $month_cycle,
                    'vendor' => $selected_vendor_id
                ])) }}"
                class="btn btn-primary">
                Download PDF
            </a>

            <button
                type="button"
                class="btn"
                onclick="window.print()">
                Print
            </button>
        </div>
    </div>

    <div class="summary">

        <div class="box">
            <div class="label">Month</div>
            <div class="value">
                {{ $month_label }}
            </div>
        </div>

        <div class="box">
            <div class="label">Period</div>
            <div class="value">
                {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                -
                {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
            </div>
        </div>

        <div class="box">
            <div class="label">Vendor</div>
            <div class="value">
                {{ $selected_vendor_name ?: 'All Vendors' }}
            </div>
        </div>

        <div class="box">
            <div class="label">Total Purchase</div>
            <div class="value total">
                Rs. {{ number_format($total, 2) }}
            </div>
        </div>

    </div>

    @if($selected_vendor_id === null)

        <div class="vendor-summary">
            <h3>Vendor Summary</h3>

            <table>
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th class="right">Purchasing Days</th>
                        <th class="right">Total Purchase</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($vendors as $vendor)
                    <tr>
                        <td>
                            {{ $vendor['vendor_name'] }}
                        </td>

                        <td class="right">
                            {{ count($vendor['dates']) }}
                        </td>

                        <td class="right">
                            <strong>
                                Rs. {{ number_format($vendor['total'], 2) }}
                            </strong>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    @endif

    @forelse($vendors as $vendor)

        <section class="vendor">

            <div class="vendor-head">
                <span>
                    {{ $vendor['vendor_name'] }}
                </span>

                <span>
                    Rs. {{ number_format($vendor['total'], 2) }}
                </span>
            </div>

            @foreach($vendor['dates'] as $date => $items)

                <div class="date-head">
                    {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item</th>
                            <th class="right">Qty</th>
                            <th class="right">Avg Rate</th>
                            <th class="right">Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="muted">
                                {{ $item['sku'] }}
                            </td>

                            <td>
                                {{ $item['item'] }}
                            </td>

                            <td class="right">
                                {{ rtrim(rtrim(number_format($item['qty'], 3, '.', ''), '0'), '.') }}
                                {{ $item['uom'] }}
                            </td>

                            <td class="right">
                                {{ number_format($item['rate'], 2) }}
                            </td>

                            <td class="right">
                                {{ number_format($item['amount'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            @endforeach

        </section>

    @empty

        <div style="padding:40px;text-align:center;color:#777">
            No purchasing data found.
        </div>

    @endforelse

</div>
</body>
</html>
