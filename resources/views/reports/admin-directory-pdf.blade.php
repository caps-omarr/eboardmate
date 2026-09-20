<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List of Boarding House Owners and Landlords/Landladies</title>
    <style>
        @page {
            size: letter portrait;
            margin: 10mm 12mm 12mm 12mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #000000;
            line-height: 1.3;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header-banner {
            width: 100%;
            text-align: center;
            margin-bottom: 12px;
        }

        .header-banner img {
            width: 100%;
            max-height: 90px;
            object-fit: contain;
        }

        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 13.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 10px 0 14px 0;
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
            font-size: 9px;
            letter-spacing: 0.3px;
        }

        .col-no {
            width: 5%;
            text-align: center;
        }

        .col-owner {
            width: 25%;
            text-align: left;
        }

        .col-bh {
            width: 25%;
            text-align: left;
        }

        .col-address {
            width: 29%;
            text-align: left;
        }

        .col-cell {
            width: 16%;
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 500;
        }

        .text-center {
            text-align: center;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .footer-note {
            margin-top: 20px;
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
        LIST OF BOARDING HOUSE OWNERS AND LANDLORDS/LANDLADIES
    </div>

    <!-- 📊 OFFICIAL TABLE FORMAT (MATCHES ATTACHED TPC DIRECTORY DOCUMENT) -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-no">NO.</th>
                <th class="col-owner">NAME OF OWNER</th>
                <th class="col-bh">NAME OF BOARDING HOUSE</th>
                <th class="col-address">ADDRESS</th>
                <th class="col-cell">CELL #</th>
            </tr>
        </thead>
        <tbody>
            @forelse($boardingHouses as $index => $bh)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-owner text-uppercase">{{ $bh['owner_name'] }}</td>
                    <td class="col-bh text-uppercase">{{ $bh['name'] }}</td>
                    <td class="col-address text-uppercase">{{ $bh['address'] }}</td>
                    <td class="col-cell">{{ $bh['cell_number'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px;">
                        No boarding house records found.
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
