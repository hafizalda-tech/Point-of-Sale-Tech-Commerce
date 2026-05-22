<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

Carbon::setLocale('id');
setlocale(LC_TIME, 'id_ID');

class DashboardController extends Controller
{
    public function index()
    {
        $kategori = Kategori::count();
        $produk = Produk::count();
        $supplier = Supplier::count();
        $member = Member::count();

        // Ambil tanggal hari ini dan kemarin
        $tanggal_hari_ini = date('Y-m-d');
        $tanggal_kemarin = date('Y-m-d', strtotime('-1 day', strtotime($tanggal_hari_ini)));

        // PENJUALAN HARI INI
        $total_penjualan = Penjualan::whereDate('created_at', $tanggal_hari_ini)->sum('bayar');

        // HPP HARI INI
        $total_hpp = DB::table('penjualan_detail_batch')
            ->join('penjualan_detail', 'penjualan_detail.id_penjualan_detail', '=', 'penjualan_detail_batch.id_penjualan_detail')
            ->join('penjualan', 'penjualan.id_penjualan', '=', 'penjualan_detail.id_penjualan')
            ->whereDate('penjualan.created_at', $tanggal_hari_ini)
            ->sum(DB::raw('penjualan_detail_batch.qty * penjualan_detail_batch.harga_beli')) ?? 0;
    
        // PENGELUARAN HARI INI
        $total_pengeluaran = Pengeluaran::whereDate('created_at', $tanggal_hari_ini)->sum('nominal');

        // LABA BERSIH HARI INI
        $total_laba_bersih = $total_penjualan - $total_hpp - $total_pengeluaran;

        // DATA KEMARIN
        $penjualan_kemarin = Penjualan::whereDate('created_at', $tanggal_kemarin)->sum('bayar');

        $hpp_kemarin = DB::table('penjualan_detail_batch')
            ->join('penjualan_detail', 'penjualan_detail.id_penjualan_detail', '=', 'penjualan_detail_batch.id_penjualan_detail')
            ->join('penjualan', 'penjualan.id_penjualan', '=', 'penjualan_detail.id_penjualan')
            ->whereDate('penjualan.created_at', $tanggal_kemarin)
            ->sum(DB::raw('penjualan_detail_batch.qty * penjualan_detail_batch.harga_beli')) ?? 0;  

        $pengeluaran_kemarin = Pengeluaran::whereDate('created_at', $tanggal_kemarin)->sum('nominal');

        $laba_kemarin = $penjualan_kemarin - $hpp_kemarin - $pengeluaran_kemarin;

        // PERSENTASE
        $persentase_penjualan = ($penjualan_kemarin != 0)
            ? (($total_penjualan - $penjualan_kemarin) / abs($penjualan_kemarin)) * 100
            : (($total_penjualan > 0) ? 100 : 0);

        $persentase_hpp = ($hpp_kemarin != 0)
            ? (($total_hpp - $hpp_kemarin) / abs($hpp_kemarin)) * 100
            : (($total_hpp > 0) ? 100 : 0);

        $persentase_pengeluaran = ($pengeluaran_kemarin != 0)
            ? (($total_pengeluaran - $pengeluaran_kemarin) / abs($pengeluaran_kemarin)) * 100
            : (($total_pengeluaran > 0) ? 100 : 0);

        $persentase_laba = ($laba_kemarin != 0)
            ? (($total_laba_bersih - $laba_kemarin) / abs($laba_kemarin)) * 100
            : (($total_laba_bersih > 0) ? 100 : ($total_laba_bersih < 0 ? -100 : 0));

        // PENDAPATAN PER HARI DALAM BULAN INI
        $tanggal_awal = date('Y-m-01');
        $tanggal_akhir = date('Y-m-d');

        $data_tanggal = [];
        $data_pendapatan = [];

        while (strtotime($tanggal_awal) <= strtotime($tanggal_akhir)) {
            $data_tanggal[] = (int) substr($tanggal_awal, 8, 2);
        
            $penjualan_hari_ini = Penjualan::whereDate('created_at', $tanggal_awal)->sum('bayar');
        
            $hpp_hari_ini = DB::table('penjualan_detail_batch')
                ->join('penjualan_detail', 'penjualan_detail.id_penjualan_detail', '=', 'penjualan_detail_batch.id_penjualan_detail')
                ->join('penjualan', 'penjualan.id_penjualan', '=', 'penjualan_detail.id_penjualan')
                ->whereDate('penjualan.created_at', $tanggal_awal)
                ->sum(DB::raw('penjualan_detail_batch.qty * penjualan_detail_batch.harga_beli')) ?? 0;
        
            $pengeluaran_hari_ini = Pengeluaran::whereDate('created_at', $tanggal_awal)->sum('nominal');
        
            $pendapatan = $penjualan_hari_ini - $hpp_hari_ini - $pengeluaran_hari_ini;
        
            $data_pendapatan[] = $pendapatan;
            $tanggal_awal = date('Y-m-d', strtotime("+1 day", strtotime($tanggal_awal)));
        }

        // PENDAPATAN PER HARI BULAN LALU
        $tanggal_awal_bulan_lalu = date('Y-m-01', strtotime('-1 month'));
        $tanggal_akhir_bulan_lalu = date('Y-m-d', strtotime('-1 month', strtotime($tanggal_akhir)));

        $data_pendapatan_bulan_lalu = [];

        $tanggal_loop = $tanggal_awal_bulan_lalu;
        while (strtotime($tanggal_loop) <= strtotime($tanggal_akhir_bulan_lalu)) {
            $pendapatan = Penjualan::whereDate('created_at', $tanggal_loop)->sum('bayar') 
                        - Pembelian::whereDate('created_at', $tanggal_loop)->sum('bayar') 
                        - Pengeluaran::whereDate('created_at', $tanggal_loop)->sum('nominal');

            $data_pendapatan_bulan_lalu[] = $pendapatan;
            $tanggal_loop = date('Y-m-d', strtotime("+1 day", strtotime($tanggal_loop)));
        }

        $tanggal_awal = date('Y-m-01');

        // KATEGORI TERLARIS HARI INI
        $kategori_terlaris = DB::table('kategori')
        ->leftJoin('produk', 'kategori.id_kategori', '=', 'produk.id_kategori')
        ->leftJoin('penjualan_detail', 'produk.id_produk', '=', 'penjualan_detail.id_produk')
        ->leftJoin('penjualan', 'penjualan.id_penjualan', '=', 'penjualan_detail.id_penjualan')
        ->select(
            'kategori.nama_kategori',
            DB::raw("COALESCE(SUM(CASE 
                WHEN DATE(penjualan.created_at) = '" . Carbon::today()->toDateString() . "'
                    AND penjualan.total_item > 0
                    AND penjualan.total_harga > 0
                THEN penjualan_detail.jumlah 
                ELSE 0 
            END), 0) as total_terjual"),
            DB::raw('COALESCE(SUM(produk.stok), 0) as total_stok')
        )
        ->groupBy('kategori.id_kategori', 'kategori.nama_kategori')
        ->orderByDesc('total_terjual')
        ->limit(4)
        ->get();
           

        if (auth()->user()->level == 1) {
            return view('admin.dashboard', compact(
                'kategori', 'produk', 'supplier', 'member', 
                'tanggal_awal', 'tanggal_akhir', 'data_tanggal', 'data_pendapatan', 'data_pendapatan_bulan_lalu',
                'total_penjualan', 'total_hpp', 'total_pengeluaran', 'total_laba_bersih',
                'persentase_penjualan', 'persentase_hpp', 'persentase_pengeluaran', 'persentase_laba',
                'kategori_terlaris'
            ));
        } elseif (auth()->user()->level == 3) {
            return view('gudang.dashboard');
        } else {
            return view('kasir.dashboard');
        }
    }
}
