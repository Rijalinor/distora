<p align="center">
  <img src="public/favicon.ico" alt="Distora Logo" width="80" height="80">
</p>

<h1 align="center">Distora Analytics</h1>

<p align="center">
  <strong>Advanced Sales & Data Analytics Dashboard for Enterprise</strong><br>
  Built with Laravel 12.0
</p>

---

## 📌 Deskripsi Singkat

**Distora Analytics** adalah sistem pelaporan dan analitik penjualan kelas *enterprise* yang dirancang khusus untuk mengelola, menganalisis, dan memvisualisasikan data transaksi penjualan & retur dari file Excel secara massal. Sistem ini mempermudah pimpinan dan tim sales dalam memantau target KPI, pertumbuhan laba, performa produk, dan produktivitas setiap tenaga penjual.

### Fitur Utama:
- 📊 **Smart Excel Importer**: Fitur *upload* data transaksi besar-besaran dengan sistem validasi, format massal, dan proteksi memori, langsung membaca file `.xlsx` distributor/principal.
- 👥 **Role-Based Access Control (RBAC)**: Pemisahan hak akses antara **Admin** (kendali penuh) dan **Salesman** (hanya melihat KPI dan riwayat pelanggan mereka sendiri).
- 📈 **Personalized Salesman Dashboard**: Layar khusus *mobile-friendly* bagi *salesman* untuk melacak progres % KPI, daftar toko yang di-cover, dan detail penjualan secara *real-time*.
- 📉 **Comprehensive Reports & Analytics**: 12+ format laporan siap pakai menggunakan Chart.js (Summary Omzet, Top Sales, Slow Moving, Retur, dll).
- 📅 **Sistem Tutup Buku**: Manajemen *Period* yang mengamankan integritas data bulanan agar laporan tidak tumpang tindih.
- ✉️ **Automated Daily Email Recap**: Robot yang mengirimkan performa tutup harian (Omzet, Top Produk, dan Target Achievers) langsung ke email Admin/Manager setiap jam 5 sore.

---

## 💻 Persyaratan Server (Requirements)

Sebelum melakukan instalasi, pastikan *Environment/Server* kamu memenuhi spesifikasi berikut:
- **PHP** >= 8.2
- **Composer** v2+
- **MySQL** >= 8.0 (atau MariaDB setara)
- Ekstensi PHP: `mbstring`, `zip`, `pdo_mysql`, `gd`, `xml`, `curl`. (Cek di `php.ini`).

---

## 🚀 Panduan Instalasi (Lokal / Windows XAMPP)

Ikuti langkah-langkah di bawah ini untuk menjalankan Distora secara lokal di komputer Windows atau Mac kamu:

### 1. Clone Repositori
Jalankan perintah ini di Terminal (Git Bash atau CMD):
```bash
git clone https://github.com/Rijalinor/distora.git
cd distora
```

### 2. Install Dependensi PHP
Jalankan Composer untuk mengunduh semua library core Laravel:
```bash
composer install
```

### 3. Konfigurasi Environment (`.env`)
Salin file konfigurasi bawaan dan ubah namanya menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` di Code Editor kamu, dan sesuaikan detail Database MySQL-nya:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=distora2
DB_USERNAME=root
DB_PASSWORD=
```
*(Catatan: Buat database kosong bernama `distora2` di phpMyAdmin sebelum lanjut ke langkah ke-5).*

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Setup Database & Akun Default (Migrasi)
Jalankan file migrasi untuk membentuk struktur tabel secara otomatis, sekaligus mengisi data awal (seeder):
```bash
php artisan migrate:fresh --seed
```

### 6. Jalankan Server
Sistem Distora Analytics sudah siap! Jalankan *local development server*:
```bash
php artisan serve
```
Aplikasi bisa diakses di browser melalui: **http://127.0.0.1:8000**

---

## 🔑 Akun Uji Coba (Login)

Setelah instalasi (dan seeding) selesai, gunakan akun bawaan ini untuk mengakses sistem:

**Sebagai Admin (Full Access)**
- **Email:** `admin@distora.com`
- **Password:** `password`

**Sebagai Salesman (Scoped Access)**
*(Note: Data dashboard salesman baru akan muncul setelah Admin melakukan Upload Excel data transaksi bulanan yang ada nama salesman-nya).*
- **Email:** `sales@distora.com`
- **Password:** `password`

---

## ⚙️ Fitur Tambahan (Cron Job / Email Otomatis)

Sistem Distora dilengkapi pengiriman Email Rekap Harian otomatis setiap pukul 17.00.
Untuk mengaktifkannya:
1. Setel *SMTP* di file `.env` kamu (contoh `MAIL_MAILER=smtp`, dst).
2. Jika jalan di server sungguhan (Linux/CPanel), tambahkan perintah ini di Cron Job server:
   `* * * * * cd /path-ke-folder-distora && php artisan schedule:run >> /dev/null 2>&1`
3. Jika dijalankan di Windows/XAMPP untuk testing lokal, buka 1 tab terminal khusus dan ketik:
   `php artisan schedule:work`

---

<p align="center">
  Didesain dan dikembangkan sebagai solusi modern <strong>Distribution Analytics System</strong>. <br>
  Semoga sukses menjaga target! 🎯🚀
</p>
