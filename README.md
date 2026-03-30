# Distora Analytics

Distora Analytics adalah sistem analitik distribusi berbasis Laravel untuk upload data penjualan, monitoring KPI, dan pengambilan keputusan berbasis data.

## Tech Stack
- Laravel 12
- PHP 8.2+
- MySQL 8+
- Python (engine forecast ML)
- Chart.js (visual analytics)

## Fitur Utama
- Import data transaksi massal dari Excel (`.xlsx`)
- RBAC: `admin` dan `salesman` dengan akses terpisah
- Dashboard KPI dan laporan analitik
- Tutup Buku & manajemen periode bulanan
- Pusat Kendali Keputusan (DSS):
  - Segmentasi toko (RFM)
  - Bundling produk
  - Stok kritis
  - Rekomendasi order pabrik
  - Stok mati
  - Growth monitoring
  - Analisis diskon
  - Audit anomali

## Upgrade ML yang Sudah Aktif
- Forecast engine Python sudah ditingkatkan dengan:
  - Auto model selection (`linear`, `ridge`, `random_forest`)
  - Walk-forward validation
  - Metrik `MAE`, `RMSE`, `MAPE`, `WAPE`
  - Confidence score + prediction interval (`low` / `high`)
  - Fallback aman untuk data tipis / nol
- Integrasi hasil ML ke menu Insight (termasuk model dan confidence)

## ML Monitoring Dashboard
Menu: `Insights -> ML Monitoring`

Ringkasan yang ditampilkan:
- Total run
- ML run vs fallback run
- Average confidence
- Average WAPE
- Average forecast error
- Log run terbaru per entitas

Tambahan:
- Tooltip hover untuk membantu membaca setiap metrik
- Filter periode, cabang, dan konteks

## Evaluasi Forecast vs Aktual
Sistem menyimpan log prediksi ke tabel `ml_forecast_runs` lalu mengevaluasi saat aktual tersedia.

Command manual evaluasi:
```bash
php artisan distora:ml-evaluate --limit=1000
```

Scheduler:
- Sudah dijadwalkan jalan otomatis tiap jam via `schedule` Laravel.

## Periode Historis
Menu Tutup Buku sekarang mendukung tahun historis lebih panjang (mis. 2024 dan sebelumnya sesuai rentang konfigurasi backend), sehingga data lama bisa tetap diarsipkan dan dibaca untuk analitik/ML.

## Catatan Logika Bisnis
- Logika diskon menggunakan `disc_item` sebagai sumber nilai diskon utama.
- Perhitungan analitik periode sudah disesuaikan agar range data konsisten dengan periode aktif yang dipilih.

## Instalasi Lokal (XAMPP/Windows)
1. Clone repo
```bash
git clone https://github.com/Rijalinor/distora.git
cd distora
```

2. Install dependency
```bash
composer install
```

3. Setup environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Konfigurasi database di `.env`, lalu jalankan migrasi + seeder
```bash
php artisan migrate:fresh --seed
```

5. Jalankan aplikasi
```bash
php artisan serve
```

Akses: `http://127.0.0.1:8000`

## Akun Default (Seeder)
- Admin
  - Email: `admin@distora.com`
  - Password: `password`
- Salesman
  - Email: `sales@distora.com`
  - Password: `password`

## Testing
```bash
php artisan test
```

## Git Workflow
```bash
git add .
git commit -m "docs: update README"
git push origin main
```

## Lisensi
Private/internal project.
