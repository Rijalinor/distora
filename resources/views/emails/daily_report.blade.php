<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Penjualan Harian - Distora</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .header {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 24px;
        }
        .card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
        }
        .card h2 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .card .amount {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            margin: 0;
        }
        .trend {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .trend-up {
            background-color: #d1fae5;
            color: #065f46;
        }
        .trend-down {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 24px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-size: 12px;
            text-transform: uppercase;
            color: #6b7280;
        }
        td {
            font-size: 14px;
        }
        .kpi-card {
            background-color: #ecfdf5;
            border: 1px solid #10b981;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kpi-name {
            font-weight: 600;
            color: #065f46;
        }
        .footer {
            background-color: #1f2937;
            color: #9ca3af;
            padding: 24px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Distora - Rekap Penjualan Harian</h1>
            <p>Laporan per tanggal: <strong>{{ $reportData['date'] }}</strong></p>
        </div>

        <!-- Content -->
        <div class="content">
            <p style="margin-top: 0;">Halo Admin/Manager,<br>Berikut adalah ringkasan penjualan harian terbaru dari sistem Distora:</p>

            <!-- Total Sales -->
            <div class="card">
                <h2>Total Omzet Hari Ini</h2>
                <div class="amount">Rp {{ number_format($reportData['salesToday'], 0, ',', '.') }}</div>
                
                @if($reportData['trend'] === 'up')
                    <div class="trend trend-up">
                        📈 +{{ $reportData['growth'] }}% vs kemarin
                    </div>
                @else
                    <div class="trend trend-down">
                        📉 -{{ $reportData['growth'] }}% vs kemarin
                    </div>
                @endif
                <div style="font-size: 12px; color: #9ca3af; margin-top: 8px;">
                    (Kemarin: Rp {{ number_format($reportData['salesYesterday'], 0, ',', '.') }})
                </div>
            </div>

            <!-- Top Products -->
            <h3 class="section-title">🏆 Top 3 Produk (Bedasarkan Omzet)</h3>
            @if($reportData['topProducts']->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['topProducts'] as $sale)
                            <tr>
                                <td style="font-weight: 500;">{{ $sale->product ? $sale->product->name : 'N/A' }}</td>
                                <td style="text-align: right;">{{ number_format($sale->total_qty) }}</td>
                                <td style="text-align: right; color: #059669; font-weight: 600;">Rp {{ number_format($sale->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: #6b7280; text-align: center; font-style: italic;">Belum ada penjualan produk hari ini.</p>
            @endif

            <!-- KPI Achievers -->
            <h3 class="section-title">⭐ Salesman Mencapai Target KPI (100%+)</h3>
            @if(count($reportData['kpiAchievers']) > 0)
                <p style="font-size: 14px; margin-bottom: 16px;">Selamat kepada tim sales berikut yang berhasil mencapai/melewati target KPI bulanan mereka hari ini:</p>
                
                @foreach($reportData['kpiAchievers'] as $achiever)
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-name">🎉 {{ $achiever['name'] }}</div>
                            <div style="font-size: 12px; color: #064e3b; margin-top: 4px;">
                                Aktual: Rp {{ number_format($achiever['achievement'], 0, ',', '.') }} / 
                                Target: Rp {{ number_format($achiever['target'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div style="font-size: 20px; font-weight: 700; color: #10b981;">
                            {{ $achiever['percentage'] }}%
                        </div>
                    </div>
                @endforeach
            @else
                <p style="color: #6b7280; font-size: 14px; font-style: italic;">Belum ada salesman yang menyentuh target 100% hari ini.</p>
            @endif

            <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280;">
                <p>Silakan login ke <a href="{{ url('/') }}" style="color: #4f46e5; text-decoration: none; font-weight: 500;">Aplikasi Distora</a> untuk melihat detail lebih lanjut.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} Distora Analytics. Email ini dibuat secara otomatis oleh sistem.<br>
            Harap tidak membalas email ini.
        </div>
    </div>
</body>
</html>
