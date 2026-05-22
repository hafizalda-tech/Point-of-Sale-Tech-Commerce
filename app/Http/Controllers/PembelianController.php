<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use PDF;

class PembelianController extends Controller
{
    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $tanggalAkhir = date('Y-m-d');
    
        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }
    
        $supplier = Supplier::orderBy('nama')->get();
    
        return view('pembelian.index', compact('supplier', 'tanggalAwal', 'tanggalAkhir'));
    }    

    public function data()
    {
        $pembelian = Pembelian::where('total_item', '>', 0)
            ->orderBy('id_pembelian', 'desc')
            ->get();

        return datatables()
            ->of($pembelian)
            ->addIndexColumn()
            ->addColumn('total_item', function ($pembelian) {
                return format_uang($pembelian->total_item);
            })
            ->addColumn('total_harga', function ($pembelian) {
                return 'Rp. '. format_uang($pembelian->total_harga);
            })
            ->addColumn('bayar', function ($pembelian) {
                return 'Rp. '. format_uang($pembelian->bayar);
            })
            ->addColumn('tanggal', function ($pembelian) {
                return tanggal_indonesia($pembelian->created_at, false);
            })
            ->addColumn('supplier', function ($pembelian) {
                return $pembelian->supplier->nama;
            })
            ->editColumn('diskon', function ($pembelian) {
                return $pembelian->diskon . '%';
            })
            ->addColumn('aksi', function ($pembelian) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`'. route('pembelian.show', $pembelian->id_pembelian) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="deleteData(`'. route('pembelian.destroy', $pembelian->id_pembelian) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create($id)
    {
        $today = Carbon::today();
    
        $pembelian = Pembelian::where('id_supplier', $id)
                              ->where('total_item', 0)
                              ->where('total_harga', 0)
                              ->where('bayar', 0)
                              ->whereDate('created_at', $today)
                              ->latest()
                              ->first();
    
        if (!$pembelian) {
            $pembelian = new Pembelian();
            $pembelian->id_supplier = $id;
            $pembelian->total_item  = 0;
            $pembelian->total_harga = 0;
            $pembelian->diskon      = 0;
            $pembelian->bayar       = 0;
            $pembelian->save();
        }
    
        session(['id_pembelian' => $pembelian->id_pembelian]);
        session(['id_supplier' => $pembelian->id_supplier]);
    
        return redirect()->route('pembelian_detail.index');
    }

    public function store(Request $request)
    {
        // Validasi produk sudah ditambahkan
        $detailCount = PembelianDetail::where('id_pembelian', $request->id_pembelian)->count();
        if ($detailCount == 0) {
            return redirect()->back()->with('error', 'Harap tambahkan produk sebelum menyimpan.');
        }
    
        // Validasi jumlah item dan harga total
        if ($request->total_item <= 0 || $request->total <= 0) {
            return redirect()->back()->with('error', 'Total item atau total harga tidak valid.');
        }
    
        // Simpan data
        $pembelian = Pembelian::findOrFail($request->id_pembelian);
        $pembelian->total_item = $request->total_item;
        $pembelian->total_harga = $request->total;
        $pembelian->diskon = $request->diskon;
        $pembelian->bayar = $request->bayar;
        $pembelian->update();
    
        $detail = PembelianDetail::where('id_pembelian', $pembelian->id_pembelian)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            $produk->stok += $item->jumlah;
            $produk->update();
        }
    
        return redirect()->route('pembelian.index')->with('success', 'Pembelian berhasil disimpan.');
    }
    

    public function show($id)
    {
        $detail = PembelianDetail::with('produk')->where('id_pembelian', $id)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">'. $detail->produk->kode_produk .'</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('merk', function ($detail) {
                return $detail->produk->merk;
            })
            ->addColumn('harga_beli', function ($detail) {
                return 'Rp. '. format_uang($detail->harga_beli);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('subtotal', function ($detail) {
                return 'Rp. '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }

    public function destroy($id)
    {
        $pembelian = Pembelian::find($id);
        $detail    = PembelianDetail::where('id_pembelian', $pembelian->id_pembelian)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok -= $item->jumlah;
                $produk->update();
            }
            $item->delete();
        }

        $pembelian->delete();

        return response(null, 204);
    }

    public function dataFiltered($awal, $akhir)
    {
        $pembelian = Pembelian::with('supplier')
            ->whereBetween(DB::raw("DATE(created_at)"), [$awal, $akhir])
            ->where('total_item', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();
    
        return datatables()
            ->of($pembelian)
            ->addIndexColumn()
            ->addColumn('total_item', fn($p) => format_uang($p->total_item))
            ->addColumn('total_harga', fn($p) => 'Rp. '. format_uang($p->total_harga))
            ->addColumn('diskon', fn($p) => $p->diskon . '%')
            ->addColumn('bayar', fn($p) => 'Rp. '. format_uang($p->bayar))
            ->addColumn('tanggal', fn($p) => tanggal_indonesia($p->created_at, false))
            ->addColumn('supplier', fn($p) => optional($p->supplier)->nama ?? '-')
    
            ->addColumn('aksi', function ($pembelian) {
                return '
                    <div class="btn-group">
                        <button onclick="showDetail(`'. route('pembelian.show', $pembelian->id_pembelian) .'`)" class="btn btn-xs btn-info btn-flat">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button onclick="deleteData(`'. route('pembelian.destroy', $pembelian->id_pembelian) .'`)" class="btn btn-xs btn-danger btn-flat">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                ';
            })
    
            // ✅ Pastikan rawColumns-nya ada agar tombol tidak tampil mentah
            ->rawColumns(['aksi'])
            ->make(true);
    }
    

    public function exportPDF($awal, $akhir)
    {
        if (!$akhir) {
            $akhir = date('Y-m-d');
        }

        $data = Pembelian::with('supplier', 'pembelianDetail.produk')
            ->whereBetween(DB::raw("DATE(created_at)"), [$awal, $akhir])
            ->where('total_item', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $total_pembelian = $data->sum('total_harga');

        $pdf = PDF::loadView('pembelian.pdf', compact('awal', 'akhir', 'data', 'total_pembelian'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan-pembelian-' . date('Y-m-d-His') . '.pdf');
    }
}
