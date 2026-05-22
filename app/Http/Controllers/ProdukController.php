<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use Illuminate\Support\Facades\DB;
use PDF;

class ProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kategori = Kategori::all()->pluck('nama_kategori', 'id_kategori');

        return view('produk.index', compact('kategori'));
    }

    public function data()
    {
        $produk = Produk::leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->select('produk.*', 'nama_kategori')
            // ->orderBy('kode_produk', 'asc')
            ->get();

        return datatables()
            ->of($produk)
            ->addIndexColumn()
            ->addColumn('select_all', function ($produk) {
                return '
                    <input type="checkbox" name="id_produk[]" value="'. $produk->id_produk .'">
                ';
            })
            ->addColumn('kode_produk', function ($produk) {
                return '<span class="label label-success">'. $produk->kode_produk .'</span>';
            })
            ->addColumn('harga_beli', function ($produk) {
                return 'Rp. '. format_uang($produk->harga_beli);
            })
            ->addColumn('harga_jual', function ($produk) {
                return 'Rp. '. format_uang($produk->harga_jual);
            })
            ->addColumn('stok', function ($produk) {
                return format_uang($produk->stok);
            })
            ->addColumn('diskon', function ($produk) {
                return $produk->diskon . '%';
            })            
            ->addColumn('aksi', function ($produk) {
                return '
                <div class="btn-group">
                    <button type="button" onclick="editForm(`'. route('produk.update', $produk->id_produk) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button type="button" onclick="deleteData(`'. route('produk.destroy', $produk->id_produk) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                    <button type="button" onclick="showDetail(`'. route('produk.riwayat', $produk->id_produk) .'`)" class="btn btn-xs btn-warning btn-flat"><i class="fa fa-eye"></i> </button>


</div>
                ';
            })
            ->rawColumns(['aksi', 'kode_produk', 'select_all'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'stok_minimum' => 'required|integer|min:1',
        ]);
    
        $produk = Produk::latest()->first() ?? new Produk();
        $request['kode_produk'] = 'P'. tambah_nol_didepan((int)$produk->id_produk +1, 6);
    
        $produk = Produk::create($request->all());
    
        $produk->stok_minimum = $request->stok_minimum;
        $produk->save();
    
        return response()->json('Data berhasil disimpan', 200);
    }
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $produk = Produk::find($id);

        return response()->json($produk);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
    
        $request->validate([
            'stok_minimum' => 'required|integer|min:1', 
        ]);
    
        // Data umum yang boleh diedit semua level
        $data = [
            'nama_produk' => $request->nama_produk,
            'merk' => $request->merk,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'id_kategori' => $request->id_kategori,
            'stok_minimum' => $request->stok_minimum,
            'barcode' => $request->barcode,
        ];
        
    
        // Hanya admin yang boleh mengubah stok & diskon
        if (auth()->user()->level == '1') {
            $data['stok'] = $request->stok;
            $data['diskon'] = $request->diskon;
        }
    
        $produk->update($data);
    
        return response()->json('Data berhasil disimpan', 200);
    }         

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $produk = Produk::find($id);
        $produk->delete();

        return response(null, 204);
    }

    public function deleteSelected(Request $request)
    {
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $produk->delete();
        }

        return response(null, 204);
    }

    public function cetakBarcode(Request $request)
    {
        $dataproduk = array();
        foreach ($request->id_produk as $id) {
            $produk = Produk::find($id);
            $dataproduk[] = $produk;
        }

        $no  = 1;
        $pdf = PDF::loadView('produk.barcode', compact('dataproduk', 'no'));
        $pdf->setPaper('a4', 'potrait');
        return $pdf->stream('produk.pdf');
    }

    public function riwayatPembelian($id)
    {
        $detail = PembelianDetail::with(['produk', 'pembelian.supplier'])
            ->where('id_produk', $id)
            ->where('sisa', '>', 0)  // ⬅ Tambahkan filter ini
            ->join('pembelian', 'pembelian.id_pembelian', '=', 'pembelian_detail.id_pembelian')
            ->orderBy('pembelian.created_at', 'asc')
            ->select('pembelian_detail.*')
            ->get();

            return datatables()
                ->of($detail)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($d) {
                    return $d->pembelian ? tanggal_indonesia($d->pembelian->created_at, false) : '-';
                })
                ->addColumn('supplier', function ($d) {
                    return $d->pembelian && $d->pembelian->supplier ? $d->pembelian->supplier->nama : '-';
                })
                ->addColumn('nama_produk', function ($d) {
                    return $d->produk->nama_produk ?? '-';
                })
                ->addColumn('harga_beli', function ($d) {
                    return 'Rp. ' . format_uang($d->harga_beli);
                })
                ->addColumn('jumlah', function ($d) {
                    return format_uang($d->jumlah);
                })
                ->addColumn('sisa', function ($d) {
                    return format_uang($d->sisa);
                })
                ->make(true);
    }

    public function exportPDF($limit = null)
    {
        // Ambil produk dan urutkan berdasarkan stok terkecil
        $produk = Produk::leftJoin('kategori', 'kategori.id_kategori', 'produk.id_kategori')
            ->select('produk.*', 'nama_kategori')
            ->orderBy('stok', 'asc'); // Urutkan stok terkecil ke terbesar
    
        if ($limit) {
            $produk->limit($limit); // Jika ada limit, batasi jumlah produk
        }
    
        $produk = $produk->get(); // Eksekusi query
    
        $pdf = PDF::loadView('produk.pdf', compact('produk'));
        $pdf->setPaper('A4', 'portrait');
    
        return $pdf->stream('data-produk.pdf'); // Menampilkan di browser
    }    

}
