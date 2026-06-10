<?php

use App\Http\Controllers\{
    DashboardController,
    KategoriController,
    LaporanController,
    ProdukController,
    MemberController,
    PengeluaranController,
    PembelianController,
    PembelianDetailController,
    ReturPembelianController,
    PenjualanController,
    PenjualanDetailController,
    SettingController,
    SupplierController,
    UserController,
};
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // ==========================================
    // KHUSUS ADMIN (LEVEL 1)
    // ==========================================
    Route::group(['middleware' => 'level:1'], function () {
        // Rute Produk Khusus Admin (Rute dasar produk ada di grup 1,3)
        Route::get('/produk/{id}/riwayat', [ProdukController::class, 'riwayatPembelian'])->name('produk.riwayat');
        Route::post('/produk/delete-selected', [ProdukController::class, 'deleteSelected'])->name('produk.delete_selected');

        // Member & Pengeluaran
        Route::get('/member/data', [MemberController::class, 'data'])->name('member.data');
        Route::post('/member/cetak-member', [MemberController::class, 'cetakMember'])->name('member.cetak_member');
        Route::resource('/member', MemberController::class);

        Route::get('/pengeluaran/data', [PengeluaranController::class, 'data'])->name('pengeluaran.data');
        Route::resource('/pengeluaran', PengeluaranController::class);

        // Hapus Penjualan (Khusus Admin)
        Route::delete('/penjualan/{id}', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');

        // Laporan, User & Setting
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/data/{awal}/{akhir}', [LaporanController::class, 'data'])->name('laporan.data');
        Route::get('/laporan/pdf/{awal}/{akhir}', [LaporanController::class, 'exportPDF'])->name('laporan.export_pdf');

        Route::get('/user/data', [UserController::class, 'data'])->name('user.data');
        Route::resource('/user', UserController::class);

        Route::get('/setting', [SettingController::class, 'index'])->name('setting.index');
        Route::get('/setting/first', [SettingController::class, 'show'])->name('setting.show');
        Route::post('/setting', [SettingController::class, 'update'])->name('setting.update');
    });

    // ==========================================
    // ADMIN & KASIR (LEVEL 1, 2)
    // ==========================================
    Route::group(['middleware' => 'level:1,2'], function () {
        // Profil
        Route::get('/profil', [UserController::class, 'profil'])->name('user.profil');
        Route::post('/profil', [UserController::class, 'updateProfil'])->name('user.update_profil');

        // Transaksi
        Route::get('/transaksi/baru', [PenjualanController::class, 'create'])->name('transaksi.baru');
        Route::post('/transaksi/simpan', [PenjualanController::class, 'store'])->name('transaksi.simpan');
        Route::get('/transaksi/selesai', [PenjualanController::class, 'selesai'])->name('transaksi.selesai');
        Route::get('/transaksi/nota-kecil', [PenjualanController::class, 'notaKecil'])->name('transaksi.nota_kecil');
        Route::get('/transaksi/nota-besar', [PenjualanController::class, 'notaBesar'])->name('transaksi.nota_besar');
        Route::get('/transaksi/{id}/data', [PenjualanDetailController::class, 'data'])->name('transaksi.data');
        Route::get('/transaksi/loadform/{diskon}/{total}/{diterima}', [PenjualanDetailController::class, 'loadForm'])->name('transaksi.load_form');
        Route::resource('/transaksi', PenjualanDetailController::class)->except('create', 'show', 'edit');

        // Penjualan (Tanpa delete/destroy karena itu khusus level 1)
        Route::get('/penjualan/data', [PenjualanController::class, 'data'])->name('penjualan.data');
        Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
        Route::post('/penjualan/update-field', [PenjualanController::class, 'updateField']);
        Route::post('/penjualan/batal', [PenjualanController::class, 'batal'])->name('penjualan.batal');
        Route::get('/penjualan/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');
        Route::get('/penjualan/data/{awal}/{akhir}', [PenjualanController::class, 'dataFiltered'])->name('penjualan.data_filtered');
        Route::get('/penjualan/pdf/{awal}/{akhir}', [PenjualanController::class, 'exportPDF'])->name('penjualan.export_pdf');  
    });

    // ==========================================
    // ADMIN & GUDANG (LEVEL 1, 3)
    // ==========================================
    Route::group(['middleware' => 'level:1,3'], function () {
        Route::get('/dashboard-gudang', [DashboardController::class, 'index'])->name('dashboard.gudang');
    
        Route::get('/kategori/data', [KategoriController::class, 'data'])->name('kategori.data');
        Route::resource('/kategori', KategoriController::class);
    
        Route::get('/produk/data', [ProdukController::class, 'data'])->name('produk.data');
        Route::post('/produk/cetak-barcode', [ProdukController::class, 'cetakBarcode'])->name('produk.cetak_barcode');
        Route::get('/produk/export-pdf', [ProdukController::class, 'exportPDF'])->name('produk.export_pdf');
        Route::resource('/produk', ProdukController::class);
    
        Route::get('/supplier/data', [SupplierController::class, 'data'])->name('supplier.data');
        Route::resource('/supplier', SupplierController::class);
    
        Route::get('/pembelian/data', [PembelianController::class, 'data'])->name('pembelian.data');
        Route::get('/pembelian/{id}/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::resource('/pembelian', PembelianController::class)->except('create');
        Route::get('/pembelian/data/{awal}/{akhir}', [PembelianController::class, 'dataFiltered'])->name('pembelian.data_filtered');
        Route::get('/pembelian/pdf/{awal}/{akhir}', [PembelianController::class, 'exportPDF'])->name('pembelian.export_pdf');
    
        Route::get('/pembelian_detail/{id}/data', [PembelianDetailController::class, 'data'])->name('pembelian_detail.data');
        Route::get('/pembelian_detail/loadform/{diskon}/{total}', [PembelianDetailController::class, 'loadForm'])->name('pembelian_detail.load_form');
        Route::resource('/pembelian_detail', PembelianDetailController::class)->except('create', 'show', 'edit');

        // Retur Pembelian yang sudah diperbaiki error-nya
        Route::get('/retur_pembelian/load-produk', [ReturPembelianController::class, 'loadProduk'])->name('retur_pembelian.load_produk');
        Route::get('/retur_pembelian/data', [ReturPembelianController::class, 'data'])->name('retur_pembelian.data');
        Route::get('/retur_pembelian/show/{id_retur}', [ReturPembelianController::class, 'show'])->name('retur_pembelian.show');
        Route::resource('/retur_pembelian', ReturPembelianController::class)->except('show'); // <-- Ditambahkan except('show')
    });
});
