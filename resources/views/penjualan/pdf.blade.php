<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laporan Penjualan</title>

    <link rel="stylesheet" href="{{ asset('/AdminLTE-2/bower_components/bootstrap/dist/css/bootstrap.min.css') }}">
</head>
<body>
    <h3 class="text-center">Laporan Penjualan</h3>
    <h4 class="text-center">
        Tanggal {{ tanggal_indonesia($awal, false) }} s/d Tanggal {{ tanggal_indonesia($akhir, false) }}
    </h4>

    <table class="table table-striped">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 15%;">Kasir</th>
                <th style="width: 25%;">Produk</th>
                <th style="width: 7%;">Item</th>
                <th style="width: 10%;">Harga</th>
                <th style="width: 10%;">Subtotal</th>
                <th style="width: 10%;">HPP</th>
                <th style="width: 10%;">Laba Kotor</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; $grand_total = 0; $grand_hpp = 0; $grand_laba = 0; @endphp
            @foreach ($data as $penjualan)
                @php
                    $rowspan = $penjualan->penjualanDetail->count();
                    $isEven = $no % 2 == 0;
                    $bg = $isEven ? '#ffffff' : '#f9f9f9';
                @endphp

                @foreach ($penjualan->penjualanDetail as $i => $detail)
                    @php
                        $hpp = DB::table('penjualan_detail_batch')
                            ->where('id_penjualan_detail', $detail->id_penjualan_detail)
                            ->sum(DB::raw('qty * harga_beli'));
                        $laba = $detail->subtotal - $hpp;
                        $grand_total += $detail->subtotal;
                        $grand_hpp += $hpp;
                        $grand_laba += $laba;
                    @endphp
                    <tr style="background-color: {{ $bg }};">
                        @if ($i === 0)
                            <td rowspan="{{ $rowspan }}" style="vertical-align: top;">{{ $no++ }}</td>
                            <td rowspan="{{ $rowspan }}" style="vertical-align: top;">{{ tanggal_indonesia($penjualan->created_at, false) }}</td>
                            <td rowspan="{{ $rowspan }}" style="vertical-align: top;">{{ $penjualan->user->name ?? 'Tidak Ada' }}</td>
                        @endif
                        <td>{{ $detail->produk->nama_produk ?? '-' }}</td>
                        <td>{{ $detail->jumlah }}</td>
                        <td>Rp. {{ format_uang($detail->harga_jual) }}</td>
                        <td>Rp. {{ format_uang($detail->subtotal) }}</td>
                        <td>Rp. {{ format_uang($hpp) }}</td>
                        <td>Rp. {{ format_uang($laba) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>Total Penjualan</strong></td>
                <td><strong>Rp. {{ format_uang($grand_total) }}</strong></td>
                <td><strong>Rp. {{ format_uang($grand_hpp) }}</strong></td>
                <td><strong>Rp. {{ format_uang($grand_laba) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
