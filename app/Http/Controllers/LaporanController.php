<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use PDF;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $tanggalAkhir = date('Y-m-d');

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        return view('laporan.index', compact('tanggalAwal', 'tanggalAkhir'));
    }

    public function getData($awal, $akhir)
    {
        $no = 1;
        $data = array();
        $total_laba_bersih = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            $tanggal = $awal;
            $awal = date('Y-m-d', strtotime("+1 day", strtotime($awal)));

            $total_penjualan = Penjualan::whereDate('created_at', $tanggal)->sum('bayar');
            $total_pengeluaran = Pengeluaran::whereDate('created_at', $tanggal)->sum('nominal');

            // HPP dihitung berdasarkan jumlah produk terjual * harga beli terakhir
            $hpp = \DB::table('penjualan_detail_batch')
                ->join('penjualan_detail', 'penjualan_detail.id_penjualan_detail', '=', 'penjualan_detail_batch.id_penjualan_detail')
                ->join('penjualan', 'penjualan.id_penjualan', '=', 'penjualan_detail.id_penjualan')
                ->whereDate('penjualan.created_at', $tanggal)
                ->sum(\DB::raw('penjualan_detail_batch.qty * penjualan_detail_batch.harga_beli')) ?? 0;

            $laba_kotor = $total_penjualan - $hpp;
            $laba_bersih = $laba_kotor - $total_pengeluaran;
            $total_laba_bersih += $laba_bersih;

            $row = array();
            $row['DT_RowIndex'] = $no++;
            $row['tanggal'] = tanggal_indonesia($tanggal, false);
            $row['penjualan'] = format_uang($total_penjualan);
            $row['hpp'] = format_uang($hpp);
            $row['laba_kotor'] = format_uang($laba_kotor);
            $row['pengeluaran'] = format_uang($total_pengeluaran);
            $row['laba_bersih'] = format_uang($laba_bersih);

            $data[] = $row;
        }

        $data[] = [
            'DT_RowIndex' => '',
            'tanggal' => '',
            'penjualan' => '',
            'hpp' => '',
            'laba_kotor' => '',
            'pengeluaran' => 'Total Laba Bersih',
            'laba_bersih' => 'Rp. ' . format_uang($total_laba_bersih),
        ];

        return $data;
    }


    public function data($awal, $akhir)
    {
        $data = $this->getData($awal, $akhir);

        return datatables()
            ->of($data)
            ->make(true);
    }

    public function exportPDF($awal, $akhir)
    {
        $data = $this->getData($awal, $akhir);
        $pdf  = PDF::loadView('laporan.pdf', compact('awal', 'akhir', 'data'));
        $pdf->setPaper('a4', 'potrait');
        
        return $pdf->stream('Laporan-pendapatan-'. date('Y-m-d-his') .'.pdf');
    }
}
