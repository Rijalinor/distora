<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDailySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'distora:daily-recap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily sales recap report to admin/managers via email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai pembuatan laporan harian...");

        // Because imported data might be historical, we'll treat the most recent transaction date as "Today"
        $latestDate = \App\Models\Transaction::max('transaction_date');
        if (!$latestDate) {
            $this->error("Tidak ada data transaksi. Laporan dihentikan.");
            return;
        }

        $today = \Carbon\Carbon::parse($latestDate);
        $yesterday = $today->copy()->subDay();
        $this->info("Menghitung data untuk tanggal: " . $today->format('Y-m-d'));

        // 1. Sales Today vs Yesterday
        $salesToday = \App\Models\Transaction::whereDate('transaction_date', $today)->sum('total');
        $salesYesterday = \App\Models\Transaction::whereDate('transaction_date', $yesterday)->sum('total');
        $growth = $salesYesterday > 0 ? round((($salesToday - $salesYesterday) / $salesYesterday) * 100, 1) : ($salesToday > 0 ? 100 : 0);
        $trend = $growth >= 0 ? 'up' : 'down';

        // 2. Top 3 Products Today
        $topProducts = \App\Models\Sale::whereHas('transaction', function($q) use ($today) {
                $q->whereDate('transaction_date', $today)
                  ->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.type')), 'sale') != 'return'");
            })
            ->selectRaw('product_id, SUM(qty) as total_qty, SUM(total) as total_sales')
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->limit(3)
            ->get();

        // 3. KPI Achievers (Salesmen who reached >= 100% as of this period)
        $activePeriod = \App\Models\Period::getActive();
        $uploadIds = $activePeriod->uploadHistories()->pluck('id');
        $kpiAchievers = [];
        $targets = \App\Models\Target::where('period_id', $activePeriod->id)->where('type', 'salesman')->get();
        
        foreach ($targets as $target) {
            $salesmanSales = \App\Models\Transaction::whereIn('upload_history_id', $uploadIds)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.sales_name')) = ?", [$target->name])
                ->sum('total');
            
            if ($salesmanSales >= $target->target_amount && $target->target_amount > 0) {
                $kpiAchievers[] = [
                    'name' => $target->name,
                    'achievement' => $salesmanSales,
                    'target' => $target->target_amount,
                    'percentage' => round(($salesmanSales / $target->target_amount) * 100, 1)
                ];
            }
        }

        // Prepare data for email
        $reportData = [
            'date' => $today->format('d F Y'),
            'salesToday' => $salesToday,
            'salesYesterday' => $salesYesterday,
            'growth' => abs($growth),
            'trend' => $trend,
            'topProducts' => $topProducts,
            'kpiAchievers' => $kpiAchievers,
        ];

        // 4. Send Email
        // Usually we fetch admin emails from DB. Here we just grab all users with role 'admin'
        $adminEmails = \App\Models\User::where('role', 'admin')->pluck('email')->toArray();
        if (empty($adminEmails)) {
            // Fallback for testing
            $adminEmails = ['admin@distora.com'];
        }

        $this->info("Mengirim email ke: " . implode(', ', $adminEmails));

        // Use standard mail facade
        // For testing locally without real SMTP, ensure MAIL_MAILER=log in .env
        \Illuminate\Support\Facades\Mail::to($adminEmails)->send(new \App\Mail\DailySalesReport($reportData));

        $this->info("Laporan Rekap Harian berhasil dikirim!");
    }
}
