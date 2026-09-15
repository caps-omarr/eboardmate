<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation &amp; Rent Payment Ledger - {{ $boardingHouse->name }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 15mm 12mm 15mm 12mm;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.35;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border-bottom: 2px solid #059669;
            padding-bottom: 8px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
            margin: 0 0 2px 0;
            letter-spacing: 0.5px;
        }

        .sub-title {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin: 0;
        }

        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #64748b;
        }

        .badge-month {
            display: inline-block;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* Summary Stats Cards */
        .metrics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 12px;
        }

        .metric-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
        }

        .metric-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .metric-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }

        /* Main Data Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .ledger-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 5px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .ledger-table td {
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: middle;
        }

        .ledger-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        .font-mono {
            font-family: monospace;
            font-size: 8.5px;
        }

        /* Checkoff blank dashed cells */
        .dashed-cell {
            border: 1px dashed #94a3b8 !important;
            background: #ffffff;
            height: 24px;
            text-align: center;
            vertical-align: middle;
            color: #94a3b8;
            font-size: 8px;
        }

        .footer-note {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #64748b;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            text-align: left;
        }

        .footer-right {
            display: table-cell;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="brand-title">E-BoardMate &bull; Property Rent Ledger</div>
                <div class="sub-title">{{ $boardingHouse->name }}</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">
                    Owner: <strong>{{ $owner->name }}</strong> 
                    @if($owner->phone) &bull; {{ $owner->phone }} @endif
                    &bull; {{ $boardingHouse->address ?? 'Talibon, Bohol' }}
                </div>
            </td>
            <td class="header-meta" style="width: 40%;">
                <div>Ledger Period:</div>
                <div class="badge-month">{{ $selectedMonthFormatted }}</div>
                <div style="margin-top: 5px;">Printed on: {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>

    <!-- Metrics Cards -->
    <table class="metrics-table">
        <tr>
            <td class="metric-box" style="width: 25%;">
                <div class="metric-label">Approved Bookings</div>
                <div class="metric-value">{{ $reservations->count() }}</div>
            </td>
            <td class="metric-box" style="width: 25%;">
                <div class="metric-label">Monthly Rent Rate</div>
                <div class="metric-value">&#8369;{{ number_format((float) $boardingHouse->rent_price, 2) }}</div>
            </td>
            <td class="metric-box" style="width: 25%;">
                <div class="metric-label">Available Capacity</div>
                <div class="metric-value">{{ $boardingHouse->available_rooms }} Rms / {{ $boardingHouse->available_bedspaces }} Beds</div>
            </td>
            <td class="metric-box" style="width: 25%;">
                <div class="metric-label">Potential Monthly</div>
                <div class="metric-value">&#8369;{{ number_format((float) ($reservations->count() * $boardingHouse->rent_price), 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Ledger Table -->
    <table class="ledger-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 3%;">#</th>
                <th style="width: 17%;">Guest Name &amp; Ref</th>
                <th style="width: 17%;">Contact &amp; Email</th>
                <th style="width: 11%;">Request Date</th>
                <th style="width: 11%;">Move-in Date</th>
                <th class="text-center" style="width: 11%;">{{ $month1Label }}</th>
                <th class="text-center" style="width: 11%;">{{ $month2Label }}</th>
                <th class="text-center" style="width: 11%;">{{ $month3Label }}</th>
                <th style="width: 8%;">Remarks / Sign</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $index => $res)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $res->guest_name }}</strong><br>
                        <span class="font-mono text-muted" style="color: #64748b;">Ref: {{ $res->reference_code }}</span>
                    </td>
                    <td>
                        <span>{{ $res->guest_phone ?? 'N/A' }}</span><br>
                        <span style="color: #64748b;">{{ $res->guest_email }}</span>
                    </td>
                    <td>
                        {{ $res->created_at?->format('M d, Y') ?? 'N/A' }}<br>
                        <span style="font-size: 8px; color: #94a3b8;">{{ $res->created_at?->format('h:i A') }}</span>
                    </td>
                    <td>
                        <strong>{{ $res->preferred_move_in_date?->format('M d, Y') ?? 'N/A' }}</strong>
                    </td>
                    <!-- 3 Blank Check-off payment cells -->
                    <td class="dashed-cell">[ &nbsp; &nbsp; ] &#8369;_______</td>
                    <td class="dashed-cell">[ &nbsp; &nbsp; ] &#8369;_______</td>
                    <td class="dashed-cell">[ &nbsp; &nbsp; ] &#8369;_______</td>
                    <td style="border: 1px solid #cbd5e1; height: 24px;">&nbsp;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 24px; color: #94a3b8;">
                        No approved reservations found for this property during the selected ledger cycle.
                    </td>
                </tr>
            @endforelse

            {{-- Extra blank rows for walk-in tenants (landlord physical record) --}}
            @for ($extra = 1; $extra <= 3; $extra++)
                <tr style="background: #ffffff;">
                    <td class="text-center" style="color: #cbd5e1;">+</td>
                    <td style="color: #94a3b8; font-style: italic;">(Walk-in Tenant)</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="dashed-cell">[ &nbsp; &nbsp; ] &#8369;_______</td>
                    <td class="dashed-cell">[ &nbsp; &nbsp; ] &#8369;_______</td>
                    <td class="dashed-cell">[ &nbsp; &nbsp; ] &#8369;_______</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <!-- Footer Note -->
    <div class="footer-note">
        <div class="footer-left">
            * This ledger is generated by E-BoardMate Owner Portal for boarding house operational and rental tracking. Check off boxes upon rent collection.
        </div>
        <div class="footer-right">
            Page 1 of 1 &bull; E-BoardMate Official Landlord Export
        </div>
    </div>

</body>
</html>
