<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laporan Pembelian</title>

    <link rel="stylesheet" href="{{ asset('/AdminLTE-2/bower_components/bootstrap/dist/css/bootstrap.min.css') }}">
</head>
<body>
    <h3 class="text-center">Laporan Pembelian</h3>
    <h4 class="text-center">
        Tanggal {{ tanggal_indonesia($awal, false) }} s/d {{ tanggal_indonesia($akhir, false) }}
    </h4>

    <table class="table table-striped">
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 15%;">Tanggal</th>
            <th style="width: 15%;">Supplier</th>
            <th style="width: 25%;">Produk</th>
            <th style="width: 10%;">Item</th>
            <th style="width: 15%;">Harga</th>
            <th style="width: 15%;">Total Harga</th>
        </tr>
    </thead>

        <tbody>
            @php $no = 1; $grand_total = 0; @endphp
            @foreach ($data as $pembelian)
                @php
                    $subtotal = 0;
                    $rowspan = $pembelian->pembelianDetail->count();
                    $bg = $no % 2 == 0 ? '#ffffff' : '#f9f9f9';
                @endphp

                @foreach ($pembelian->pembelianDetail as $i => $detail)
                    <tr style="background-color: {{ $bg }};">
                        @if ($i === 0)
                            <td rowspan="{{ $rowspan }}" style="vertical-align: top;">{{ $no++ }}</td>
                            <td rowspan="{{ $rowspan }}" style="vertical-align: top;">{{ tanggal_indonesia($pembelian->created_at, false) }}</td>
                            <td rowspan="{{ $rowspan }}" style="vertical-align: top;">{{ $pembelian->supplier->nama }}</td>
                        @endif
                        <td>{{ $detail->produk->nama_produk ?? '-' }}</td>
                        <td>{{ $detail->jumlah }}</td>
                        <td>Rp {{ format_uang($detail->harga_beli) }}</td>
                        <td>Rp {{ format_uang($detail->subtotal) }}</td>
                        @php $subtotal += $detail->subtotal; @endphp
                    </tr>
                @endforeach

                <tr style="background-color: {{ $bg }};">
                    <td colspan="5" style="border-top: none;"></td>
                    <td style="border-top: none;">Subtotal :</td>
                    <td style="border-top: none;">Rp {{ format_uang($subtotal) }}</td>
                </tr>

                @php $grand_total += $subtotal; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>Total Pembelian</strong></td>
                <td><strong>Rp {{ format_uang($grand_total) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
