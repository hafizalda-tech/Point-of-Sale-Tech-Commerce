<p align="center">
    <a href="https://github.com/hafizalda-tech" target="_blank">
        <img src="public/img/logo.png" width="120">
    </a>
</p>

## Tentang Aplikasi

Aplikasi POS (Point of Sales) dan Inventory Management adalah aplikasi yang digunakan untuk mengelola transaksi penjualan, pembelian, pengelolaan stok, serta operasional toko secara terintegrasi. Aplikasi ini dibangun menggunakan Laravel v8.* dan minimal PHP v7.4.

Selain mendukung proses transaksi kasir, aplikasi ini juga dilengkapi dengan fitur pengelolaan inventory seperti riwayat pembelian produk, retur pembelian, perhitungan HPP (Harga Pokok Penjualan), serta manajemen pengguna berdasarkan role.

### Beberapa Fitur yang tersedia:

* Dashboard Monitoring

  * Statistik Produk
  * Statistik Supplier
  * Statistik Member
  * Statistik Penjualan
  * Statistik Pembelian
  * Statistik Pengeluaran
  * Grafik Keuntungan

* Manajemen Kategori Produk

* Manajemen Produk

  * Generate Kode Produk Otomatis
  * Multiple Delete
  * Cetak Barcode
  * Riwayat Pembelian Produk
  * Export PDF Produk

* Manajemen Member atau Anggota

  * Cetak Kartu Member
  * Diskon Member

* Manajemen Supplier

* Transaksi Pengeluaran

* Transaksi Pembelian

  * Multi Produk
  * Update Stok Otomatis
  * Export PDF Pembelian

* Retur Pembelian

  * Retur Barang ke Supplier
  * Pengurangan Stok Otomatis
  * Riwayat Retur Pembelian

* Transaksi Penjualan

  * Scan Barcode
  * Diskon Member
  * Perhitungan Kembalian Otomatis
  * Cetak Nota
  * Export PDF Penjualan

* Laporan Pendapatan atau Laba & Rugi

  * Bulanan
  * Harian
  * Custom Tanggal
  * Perhitungan HPP
  * Export PDF

* Custom Tipe Nota

  * Nota Besar
  * Nota Kecil / Thermal Nota

* Manajemen User dan Profil

* Pengaturan Toko

  * Identitas Toko
  * Upload Logo Toko
  * Upload Desain Kartu Member
  * Setting Diskon Member

* Manajemen Hak Akses

  * Administrator
  * Kasir
  * Gudang

* Grafik ChartJS pada Dashboard

## Instalasi

#### Via Git

```bash
git clone https://github.com/hafizalda-tech/point-of-sale-laravel.git
```

### Download ZIP

```text
https://github.com/hafizalda-tech/point-of-sale-laravel/archive/refs/heads/main.zip
```

### Setup Aplikasi

Jalankan perintah

```bash
composer update
```

atau:

```bash
composer install
```

Copy file .env dari .env.example

```bash
cp .env.example .env
```

Konfigurasi file .env

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=point_of_sale
DB_USERNAME=root
DB_PASSWORD=
```

Opsional

```bash
APP_NAME="Point Of Sale"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
```

Generate key

```bash
php artisan key:generate
```

Migrate database

```bash
php artisan migrate
```

Seeder database

```bash
php artisan db:seed
```

Menjalankan aplikasi

```bash
php artisan serve
```

## Struktur Role

### Administrator

Memiliki akses penuh terhadap seluruh fitur aplikasi.

### Kasir

Memiliki akses terhadap:

* Dashboard
* Transaksi Penjualan
* Profil

### Gudang

Memiliki akses terhadap:

* Produk
* Supplier
* Pembelian
* Retur Pembelian
* Pengelolaan Stok

## Alur Sistem

### Barang Masuk

```text
Supplier
    ↓
Pembelian
    ↓
Stok Bertambah
```

### Retur Barang

```text
Pembelian
    ↓
Retur Pembelian
    ↓
Stok Berkurang
```

### Barang Keluar

```text
Penjualan
    ↓
Stok Berkurang
    ↓
Perhitungan HPP
    ↓
Laporan Laba
```

## License

[MIT license](https://opensource.org/licenses/MIT)
