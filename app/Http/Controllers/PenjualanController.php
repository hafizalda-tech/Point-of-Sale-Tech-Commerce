<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use PDF;

class PenjualanController extends Controller
{
    public function index(Request $request)
    {
        $tanggalAwal = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $tanggalAkhir = date('Y-m-d');

        if ($request->has('tanggal_awal') && $request->tanggal_awal != "" && $request->has('tanggal_akhir') && $request->tanggal_akhir) {
            $tanggalAwal = $request->tanggal_awal;
            $tanggalAkhir = $request->tanggal_akhir;
        }

        return view('penjualan.index', compact('tanggalAwal', 'tanggalAkhir'));
    }

    public function data()
    {
        $penjualan = Penjualan::with('member')
            ->where('total_item', '>', 0)
            ->orderBy('id_penjualan', 'desc')
            ->get();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('kode_member', function ($penjualan) {
                $member = $penjualan->member->kode_member ?? '';
                return '<span class="label label-success">'. $member .'</span>';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi', 'kode_member'])
            ->make(true);
    }

    public function create()
    {
        $today = Carbon::today();

        // ✅ Gunakan transaksi dari session jika masih ada
        if (session()->has('id_penjualan')) {
            $penjualan = Penjualan::find(session('id_penjualan'));

            if ($penjualan && $penjualan->total_item == 0 && $penjualan->total_harga == 0) {
                return redirect()->route('transaksi.index');
            }
        }

        // 🔁 CARI transaksi HANYA berdasarkan total_item dan total_harga saja
        $penjualan = Penjualan::where('id_user', auth()->id())
            ->where('total_item', 0)
            ->where('total_harga', 0)
            ->where('bayar', 0)
            ->whereDate('created_at', $today)
            ->latest()
            ->first();

        if (!$penjualan) {
            $penjualan = new Penjualan();
            $penjualan->id_member = null;
            $penjualan->total_item = 0;
            $penjualan->total_harga = 0;
            $penjualan->diskon = 0;
            $penjualan->bayar = 0;
            $penjualan->diterima = 0;
            $penjualan->id_user = auth()->id();
            $penjualan->save();
        }

        session(['id_penjualan' => $penjualan->id_penjualan]);

        return redirect()->route('transaksi.index');
    }


    public function store(Request $request)
    {
        // Periksa produk
        $detailCount = PenjualanDetail::where('id_penjualan', $request->id_penjualan)->count();
        if ($detailCount == 0) {
            return redirect()->back()->with('error', 'Harap tambahkan produk sebelum menyimpan.');
        }

        // Validasi angka
        if ($request->total_item <= 0 || $request->total <= 0) {
            return redirect()->back()->with('error', 'Total item atau total harga tidak valid.');
        }

        if ($request->diterima < $request->bayar) {
            return redirect()->back()->with('error', 'Uang yang diterima kurang dari total yang harus dibayar.');
        }

        // VALIDASI STOK PER BATCH
        $detail = PenjualanDetail::where('id_penjualan', $request->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $stokTersedia = DB::table('pembelian_detail')
                    ->where('id_produk', $item->id_produk)
                    ->where('sisa', '>', 0)
                    ->sum('sisa');

                if ($stokTersedia < $item->jumlah) {
                    return redirect()->back()->with('error', 'Stok untuk "' . $produk->nama_produk . '" tidak mencukupi. Tersedia: ' . $stokTersedia);
                }
            }
        }

        DB::beginTransaction();
        try {
            foreach ($detail as $item) {
                $sisa_jual = $item->jumlah;

                $pembelianDetails = DB::table('pembelian_detail')
                    ->join('pembelian', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
                    ->where('pembelian_detail.id_produk', $item->id_produk)
                    ->where('pembelian_detail.sisa', '>', 0)
                    ->orderBy('pembelian.created_at', 'asc')
                    ->select('pembelian_detail.id_pembelian_detail', 'pembelian_detail.sisa', 'pembelian_detail.harga_beli')
                    ->get();

                foreach ($pembelianDetails as $pd) {
                    if ($sisa_jual <= 0) break;

                    $kurangi = min($pd->sisa, $sisa_jual);
                    $sisa_jual -= $kurangi;

                    DB::table('pembelian_detail')
                        ->where('id_pembelian_detail', $pd->id_pembelian_detail)
                        ->update([
                            'sisa' => DB::raw("sisa - $kurangi")
                        ]);

                    $now = now();
                    DB::table('penjualan_detail_batch')->insert([
                        'id_penjualan_detail' => $item->id_penjualan_detail,
                        'id_pembelian_detail' => $pd->id_pembelian_detail,
                        'qty' => $kurangi,
                        'harga_beli' => $pd->harga_beli,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $produk = Produk::find($item->id_produk);
                if ($produk) {
                    $produk->stok -= $item->jumlah;
                    $produk->save();
                }

                $item->diskon = $request->diskon;
                $item->save();
            }

            $penjualan = Penjualan::findOrFail($request->id_penjualan);
            $penjualan->id_member = $request->id_member;
            $penjualan->total_item = $request->total_item;
            $penjualan->total_harga = $request->total;
            $penjualan->diskon = $request->diskon;
            $penjualan->bayar = $request->bayar;
            $penjualan->diterima = $request->diterima;
            $penjualan->save();

            DB::commit();

            return redirect()->route('transaksi.selesai')->with('success', 'Transaksi berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi error saat menyimpan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get()
            ->map(function ($item) {
                $batch = DB::table('penjualan_detail_batch')
                    ->where('id_penjualan_detail', $item->id_penjualan_detail)
                    ->select('qty', 'harga_beli')
                    ->get();

                $item->batch_info = $batch->map(function ($b) {
                    return $b->qty . ' pcs x Rp. ' . format_uang($b->harga_beli);
                })->implode('<br>');

                $item->total_hpp = $batch->sum(function ($b) {
                    return $b->qty * $b->harga_beli;
                });

                $item->laba_kotor = $item->subtotal - $item->total_hpp;

                return $item;
            });

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
                return $detail->batch_info;
            })
            ->addColumn('hpp', function ($detail) {
                return 'Rp. ' . format_uang($detail->total_hpp);
            })
            ->addColumn('laba_kotor', function ($detail) {
                return 'Rp. ' . format_uang($detail->laba_kotor);
            })
            ->addColumn('harga_jual', function ($detail) {
                return 'Rp. '. format_uang($detail->harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('subtotal', function ($detail) {
                return 'Rp. '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk', 'harga_beli'])
            ->make(true);
    }

    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);

        if (!$penjualan) {
            return response()->json(['error' => 'Transaksi tidak ditemukan.'], 404);
        }

        $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();

        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok += $item->jumlah;
                $produk->save();
            }

            // Restore sisa pembelian_detail (reverse FIFO)
            $sisa_retur = $item->jumlah;

            $pembelianDetails = DB::table('pembelian_detail')
                ->join('pembelian', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
                ->where('pembelian_detail.id_produk', $item->id_produk)
                ->orderBy('pembelian.created_at', 'desc') // Balikin ke batch terbaru dulu
                ->select('pembelian_detail.id_pembelian_detail', 'pembelian_detail.jumlah', 'pembelian_detail.sisa')
                ->get();

            foreach ($pembelianDetails as $pd) {
                if ($sisa_retur <= 0) break;

                $bisaTambah = $pd->jumlah - $pd->sisa;
                $tambah = min($bisaTambah, $sisa_retur);
                $sisa_retur -= $tambah;

                DB::table('pembelian_detail')
                    ->where('id_pembelian_detail', $pd->id_pembelian_detail)
                    ->update([
                        'sisa' => DB::raw("sisa + $tambah")
                    ]);
            }

            $item->delete();
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function selesai()
    {
        $setting = Setting::first();

        return view('penjualan.selesai', compact('setting'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();
        
        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }

    public function notaBesar()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $pdf->setPaper(0,0,609,440, 'potrait');
        return $pdf->stream('Transaksi-'. date('Y-m-d-his') .'.pdf');
    }

    public function dataFiltered($awal, $akhir)
    {
        $penjualan = Penjualan::with('member')
            ->whereBetween(DB::raw("DATE(created_at)"), [$awal, $akhir])
            ->where('total_item', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();    

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('kode_member', function ($penjualan) {
                $member = $penjualan->member->kode_member ?? '';
                return '<span class="label label-success">'. $member .'</span>';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi', 'kode_member'])
            ->make(true);
    }

    public function updateField(Request $request)
    {
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (!$penjualan) return response()->json(['error' => 'Transaksi tidak ditemukan'], 400);

        $field = $request->field;
        $value = $request->value;

        if (!in_array($field, ['id_member', 'diterima'])) {
            return response()->json(['error' => 'Field tidak diperbolehkan'], 400);
        }

        $penjualan->$field = $value;
        $penjualan->save();

        return response()->json(['success' => true]);
    }

    public function batal()
    {
        $id_penjualan = session('id_penjualan');

        if (!$id_penjualan) {
            return redirect()->route('transaksi.baru')->with('error', 'Tidak ada transaksi aktif.');
        }

        $penjualan = Penjualan::find($id_penjualan);

        if (!$penjualan) {
            session()->forget('id_penjualan');
            return redirect()->route('transaksi.baru')->with('error', 'Transaksi tidak ditemukan di database.');
        }

        PenjualanDetail::where('id_penjualan', $id_penjualan)->delete();

        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->id_member = null;
        $penjualan->save();

        session()->forget('id_penjualan'); // ❗ Tambahan ini penting

        return redirect()->route('transaksi.baru')->with('success', 'Transaksi berhasil dibatalkan.');
    }

    public function exportPDF($awal, $akhir)
    {
        if (!$akhir) {
            $akhir = date('Y-m-d');
        }

        $data = Penjualan::with('member', 'user', 'penjualanDetail.produk')
            ->whereBetween(DB::raw("DATE(created_at)"), [$awal, $akhir])
            ->where('total_item', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $total_penjualan = $data->sum('total_harga');

        $total_hpp = DB::table('penjualan_detail_batch')
            ->join('penjualan_detail', 'penjualan_detail.id_penjualan_detail', '=', 'penjualan_detail_batch.id_penjualan_detail')
            ->join('penjualan', 'penjualan.id_penjualan', '=', 'penjualan_detail.id_penjualan')
            ->whereBetween(DB::raw("DATE(penjualan.created_at)"), [$awal, $akhir])
            ->sum(DB::raw('penjualan_detail_batch.qty * penjualan_detail_batch.harga_beli'));

        $total_laba_kotor = $total_penjualan - $total_hpp;

        $pdf = PDF::loadView('penjualan.pdf', compact(
            'awal', 'akhir', 'data', 'total_penjualan', 'total_hpp', 'total_laba_kotor'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan-penjualan-' . date('Y-m-d-His') . '.pdf');
    }
 
    
}