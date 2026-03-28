# Distora2: Setup Guide for New Laptop 💻🚀

Ikuti langkah-langkah di bawah ini untuk menjalankan Distora2 di laptop baru Chef:

### 1. Persiapan Lingkungan (Prerequisites)
Pastikan laptop baru sudah terinstall aplikasi berikut:
- **XAMPP**: Download yang versi **PHP 8.2** atau lebih baru.
- **Git**: Untuk mendownload source code.
- **Composer**: Untuk menginstall library PHP (Laravel).
- **Python (3.10+)**: Pastikan saat install, centang opsi **"Add Python to PATH"**.

---

### 2. Clone & Install Dependencies
Buka Terminal/Command Prompt di folder `htdocs` XAMPP Anda:
```bash
# 1. Clone repository
git clone https://github.com/Rijalinor/distora.git distora2
cd distora2

# 2. Install Library Laravel
composer install
```

---

### 3. Konfigurasi Database (.env)
1. Salin file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
2. Buka file `.env` pakai Notepad/VSCode, lalu sesuaikan bagian ini:
   ```env
   DB_DATABASE=distora2
   DB_USERNAME=root
   DB_PASSWORD=
   
   # Pastikan path python benar (sesuaikan jika python terinstall di folder lain)
   PYTHON_PATH=python
   ```
3. Generate Key Laravel:
   ```bash
   php artisan key:generate
   ```

---

### 4. Setup Database
1. Buka **phpMyAdmin** (http://localhost/phpmyadmin).
2. Buat database baru dengan nama `distora2`.
3. Jalankan migrasi di terminal:
   ```bash
   php artisan migrate
   ```

---

### 5. Setup Machine Learning (Penting! 🎓)
Distora2 butuh Python untuk menjalankan AI S1-nya. Jalankan perintah ini di terminal:
```bash
pip install pandas scikit-learn numpy
```

---

### 6. Jalankan Aplikasi
Sekarang Chef sudah siap! Jalankan perintah ini:
```bash
php artisan serve
```
Buka browser di alamat: **http://127.0.0.1:8000**

---

### Tips Migrasi Data Lama:
Jika Chef mau pindah data dari laptop lama:
1. **Export Database**: Dari phpMyAdmin laptop lama, export database `distora2` ke file `.sql`.
2. **Import Database**: Di laptop baru, import file `.sql` tersebut ke database `distora2`.
3. **Folder Storage**: Copy folder `storage/app/public` dari laptop lama ke laptop baru (isi bukti transfer/foto jika ada).

Selamat bertempur di laptop baru, Chef! 🚀🧠🔥
