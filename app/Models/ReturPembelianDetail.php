<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPembelianDetail extends Model
{
    protected $table = 'retur_pembelian_detail';
    protected $primaryKey = 'id_detail';
    protected $guarded = [];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    public function retur()
    {
        return $this->belongsTo(ReturPembelian::class, 'id_retur');
    }
}
