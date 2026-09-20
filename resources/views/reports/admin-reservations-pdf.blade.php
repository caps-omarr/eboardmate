<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Audit Master List</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm 12mm 12mm 12mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #000000;
            line-height: 1.3;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header-banner {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }

        .header-banner img {
            width: 100%;
            max-height: 85px;
            object-fit: contain;
        }

        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 13.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 8px 0 12px 0;
            color: #000000;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000000;
            padding: 5px 6px;
            vertical-align: middle;
            color: #000000;
        }

        .data-table thead th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            font-size: 8.5px;
            letter-spacing: 0.3px;
        }

        .col-no {
            width: 4%;
            text-align: center;
        }

        .col-ref {
            width: 13%;
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }

        .col-guest {
            width: 18%;
            text-align: left;
        }

        .col-bh {
            width: 22%;
            text-align: left;
        }

        .col-date {
            width: 11%;
            text-align: center;
        }

        .col-cell {
            width: 11%;
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
        }

        .col-status {
            width: 10%;
            text-align: center;
            text-transform: uppercase;
            font-weight: 600;
        }

        .col-created {
            width: 11%;
            text-align: center;
            font-size: 8px;
        }

        .text-center {
            text-align: center;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .footer-note {
            margin-top: 15px;
            text-align: right;
            font-size: 8px;
            color: #555555;
        }
    </style>
</head>
<body>

    <!-- 🏛️ OFFICIAL TPC INSTITUTIONAL HEADER BANNER -->
    <div class="header-banner">
        @if(!empty($bannerBase64))
            <img src="{{ $bannerBase64 }}" alt="Talibon Polytechnic College Banner" />
        @endif
    </div>

    <!-- 📋 OFFICIAL DOCUMENT TITLE -->
    <div class="report-title">
        LIST OF BOARDING HOUSE RESERVATIONS AND BOOKING AUDIT
    </div>

    <!-- 📊 OFFICIAL TABLE FORMAT -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-no">NO.</th>
                <th class="col-ref">REF CODE</th>
                <th class="col-guest">GUEST NAME</th>
                <th class="col-bh">NAME OF BOARDING HOUSE</th>
                <th class="col-date">MOVE-IN DATE</th>
                <th class="col-cell">CELL #</th>
                <th class="col-status">STATUS</th>
                <th class="col-created">SUBMITTED</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $index => $res)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-ref">{{ $res['reference_code'] }}</td>
                    <td class="col-guest text-uppercase">{{ $res['guest_name'] }}</td>
                    <td class="col-bh text-uppercase">{{ $res['boarding_house_name'] }}</td>
                    <td class="col-date">{{ $res['preferred_move_in_date'] }}</td>
                    <td class="col-cell">{{ $res['guest_phone_formatted'] }}</td>
                    <td class="col-status">{{ $res['status_label'] }}</td>
                    <td class="col-created">{{ $res['created_at'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        No reservation records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Talibon Polytechnic College &bull; E-BoardMate System Official Report &bull; Generated: {{ $generatedAt }}
    </div>

</body>
</html>
