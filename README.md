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
  - Stock Redistribution Advisor (optimasi antar gudang)
  - Stok mati
  - Growth monitoring
  - Analisis diskon
  - Audit anomali
  - Tax & VAT compliance

## Penjelasan Fitur (Fiturnya + Value)

### Segmentasi Toko (RFM)
Sistem membaca pola belanja outlet berdasarkan recency, frequency, dan monetary.

**Fiturnya:** Outlet dikelompokkan otomatis ke segmen prioritas (mis. Sultan, Gold, Sleeper) untuk fokus kunjungan sales.

**Value:** Tim lapangan lebih tepat sasaran, waktu kunjungan lebih efisien, dan potensi omset lebih cepat naik.

### Rekomendasi Bundling
Sistem menganalisis produk yang sering dibeli bersamaan.

**Fiturnya:** Menampilkan pasangan produk terbaik untuk strategi bundling dan cross-sell.

**Value:** Nilai transaksi per nota meningkat tanpa harus menambah biaya akuisisi pelanggan baru.

### Monitoring Stok Kritis
Sistem memantau stok vs laju penjualan harian/mingguan.

**Fiturnya:** Produk dengan risiko habis dalam waktu dekat ditandai otomatis sebagai prioritas tindakan.

**Value:** Mengurangi kehilangan penjualan karena stockout.

### Rekomendasi Order Pabrik
Sistem menghitung kebutuhan order dari histori konsumsi, stok saat ini, buffer, dan lead time.

**Fiturnya:** Saran jumlah order lebih realistis dengan mempertimbangkan waktu kirim pabrik.

**Value:** Mengurangi risiko kehabisan barang sekaligus menekan overstock.

### Stock Redistribution Advisor (Optimasi Antar Gudang)
Kamu punya data stok dari beberapa lokasi. Seringkali, sebuah barang habis di satu cabang tapi menumpuk di cabang lain.

**Fiturnya:** Sistem membandingkan SWC (Sales Week Coverage) antar gudang. Jika cabang A SWC-nya 0 (habis) dan cabang B SWC-nya sangat tinggi (kelebihan), sistem menyarankan mutasi stok antar cabang sebelum order pabrik.

**Value:** Menghemat modal perusahaan karena stok idle di cabang lain bisa dimanfaatkan lebih dulu.

### Stok Mati (Dead Stock)
Sistem mendeteksi produk yang lama tidak bergerak.

**Fiturnya:** Daftar dead stock ditampilkan untuk tindakan cepat (promo, bundling, relokasi, atau stop order).

**Value:** Perputaran stok membaik dan biaya simpan gudang turun.

### Growth Monitoring
Sistem membandingkan performa antar periode (bulan ke bulan).

**Fiturnya:** Menampilkan tren naik/turun per produk, cabang, atau principal.

**Value:** Manajemen bisa cepat koreksi strategi sebelum dampak negatif makin besar.

### Analisis Diskon
Sistem mengevaluasi efektivitas diskon berdasarkan data transaksi.

**Fiturnya:** Nilai diskon utama dibaca dari `disc_item` untuk konsistensi analisis margin.

**Value:** Strategi promo lebih sehat: diskon tepat sasaran, margin tetap terjaga.

### Audit Anomali
Sistem mencari pola transaksi yang tidak wajar.

**Fiturnya:** Menandai outlier penjualan/retur/harga untuk ditinjau ulang.

**Value:** Mengurangi risiko kesalahan data, fraud, atau keputusan yang bias.

### ML Monitoring Dashboard
Sistem memantau kualitas prediksi AI/ML secara periodik.

**Fiturnya:** Menampilkan total run, confidence, error, WAPE, serta log model vs fallback.

**Value:** Tim tahu kapan model masih sehat dan kapan perlu retrain/perbaikan data.

### Tax & VAT Compliance Automator
Sistem merekap PPN keluaran bulanan dari data transaksi.

**Fiturnya:** Ringkasan DPP, VAT, dan kepatuhan pengisian tax invoice per bulan/cabang.

**Value:** Menghemat waktu tim akuntansi karena rekap pajak tidak perlu lagi dilakukan manual dari Excel.

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
