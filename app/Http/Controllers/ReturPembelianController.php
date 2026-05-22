<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReturPembelian;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\PembelianDetail;
use App\Models\ReturPembelianDetail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReturPembelianController extends Controller
{
    public function index()
    {
        $pembelian = Pembelian::all();
        $produk = Produk::all();
        $supplier = Supplier::orderBy('nama')->get();

        return view('retur_pembelian.index', compact('pembelian', 'produk', 'supplier'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_supplier' => 'required|exists:supplier,id_supplier',
            'tanggal_pembelian' => 'required|date',
            'produk_id' => 'required|array',
            'produk_id.*' => 'exists:produk,id_produk',
            'jumlah_retur' => 'required|array',
            'jumlah_retur.*' => 'nullable|integer|min:0',
        ]);
    
        $pembelian = Pembelian::where('id_supplier', $request->id_supplier)
            ->whereDate('created_at', $request->tanggal_pembelian)
            ->first();
    
        if (!$pembelian) {
            return back()->with('error', 'Pembelian tidak ditemukan.');
        }
    
        // Buat data retur utama
        $retur = ReturPembelian::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'tanggal_retur' => now(),
            'id_user' => auth()->id(),
        ]);
    
        $adaRetur = false;
    
        foreach ($request->produk_id as $id_produk) {
            $jumlah = (int) ($request->jumlah_retur[$id_produk] ?? 0);
    
            // Lewati jika jumlah kosong atau nol
            if ($jumlah < 1) continue;
    
            $detailPembelian = PembelianDetail::where('id_pembelian', $pembelian->id_pembelian)
                ->where('id_produk', $id_produk)
                ->first();
    
            if (!$detailPembelian) continue;
    
            if ($jumlah > $detailPembelian->jumlah) {
                return back()->with('error', "Jumlah retur untuk produk {$detailPembelian->produk->nama_produk} melebihi jumlah pembelian.");
            }
    
            Produk::where('id_produk', $id_produk)->decrement('stok', $jumlah);
    
            ReturPembelianDetail::create([
                'id_retur' => $retur->id_retur,
                'id_produk' => $id_produk,
                'jumlah' => $jumlah,
                'alasan' => $request->alasan[$id_produk] ?? '-',
            ]);
    
            $adaRetur = true;
        }
    
        // Jika tidak ada produk yang benar-benar diretur, hapus retur utama
        if (!$adaRetur) {
            $retur->delete();
            return back()->with('error', 'Tidak ada produk yang diretur.');
        }
    
        return redirect()->route('retur_pembelian.index')->with('success', 'Retur berhasil disimpan.');
    }
    

    public function data()
    {
        $retur = ReturPembelian::with(['detail', 'user', 'pembelian.supplier'])->latest()->get();
    
        return datatables()
            ->of($retur)
            ->addIndexColumn()
            ->addColumn('supplier', fn($row) => $row->pembelian->supplier->nama ?? '-')
            ->addColumn('tanggal_retur', fn($row) => Carbon::parse($row->tanggal_retur)->translatedFormat('d F Y'))
            ->addColumn('tanggal_pembelian', fn($row) => Carbon::parse($row->pembelian->created_at)->translatedFormat('d F Y'))
    
            // Kolom baru: total jenis produk (baris data retur_detail)
            ->addColumn('total_produk', fn($row) => $row->detail->count())
    
            // Kolom lama: jumlah semua item retur
            ->addColumn('total_item', fn($row) => $row->detail->sum('jumlah'))
    
            ->addColumn('alasan', fn($row) => $row->detail->pluck('alasan')->implode(', '))
            ->addColumn('aksi', function ($row) {
                return '
                    <div class="btn-group">
                        <button onclick="showDetail(`'. route('retur_pembelian.show', $row->id_retur) .'`)" class="btn btn-xs btn-info btn-flat">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button onclick="deleteData(`'. route('retur_pembelian.destroy', $row->id_retur) .'`)" class="btn btn-xs btn-danger btn-flat">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    

    public function loadProduk(Request $request)
    {
        if (! $request->has(['supplier', 'tanggal'])) {
            return response('Parameter kurang', 400);
        }
    
        $pembelianDetails = PembelianDetail::whereHas('pembelian', function ($q) use ($request) {
            $q->where('id_supplier', $request->supplier)
              ->whereDate('created_at', $request->tanggal);
        })
        ->with(['produk', 'pembelian']) // pastikan relasi pembelian dan produk di-load
        ->get();
    
        // Gabungkan berdasarkan id_produk
        $produkGrouped = $pembelianDetails->groupBy('id_produk')->map(function ($group) {
            $jumlahTotal = $group->sum('jumlah');
    
            // Ambil pembelian pertama untuk referensi id_pembelian
            $first = $group->first();
    
            $jumlahRetur = ReturPembelianDetail::whereHas('retur', function ($q) use ($group) {
                $q->whereIn('id_pembelian', $group->pluck('id_pembelian'));
            })->where('id_produk', $first->id_produk)->sum('jumlah');
    
            $first->jumlah = $jumlahTotal;
            $first->jumlah_sisa = $jumlahTotal - $jumlahRetur;
    
            return $first;
        })->filter(function ($item) {
            return $item->jumlah_sisa > 0;
        });
    
        if ($produkGrouped->isEmpty()) {
            return response('Tidak ada produk ditemukan.', 404);
        }
    
        return view('retur_pembelian.partials.produk-list', [
            'produk' => $produkGrouped
        ]);
    }
    
    


    public function show($id_retur)
    {
        $detail = ReturPembelianDetail::with('produk')->where('id_retur', $id_retur)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', fn($item) => '<span class="label label-success">'. $item->produk->kode_produk .'</span>')
            ->addColumn('nama_produk', fn($item) => $item->produk->nama_produk)
            ->addColumn('merk', fn($item) => $item->produk->merk)
            ->addColumn('jumlah', fn($item) => $item->jumlah)
            ->addColumn('alasan', fn($item) => $item->alasan ?? '-')
            ->rawColumns(['kode_produk'])
            ->make(true);
    }

    public function destroy($id_retur)
    {
        $retur = ReturPembelian::with('detail')->findOrFail($id_retur);

        foreach ($retur->detail as $detail) {
            $produk = Produk::find($detail->id_produk);
            if ($produk) {
                $produk->stok += $detail->jumlah;
                $produk->save();
            }
            $detail->delete();
        }

        $retur->delete();
        return response(null, 204);
    }
}
