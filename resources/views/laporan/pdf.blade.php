<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laporan Pendapatan</title>

    <link rel="stylesheet" href="{{ asset('/AdminLTE-2/bower_components/bootstrap/dist/css/bootstrap.min.css') }}">
</head>
<body>
    <h3 class="text-center">Laporan Pendapatan</h3>
    <h4 class="text-center">
        Tanggal {{ tanggal_indonesia($awal, false) }}
        s/d
        Tanggal {{ tanggal_indonesia($akhir, false) }}
    </h4>

    <table class="table table-striped">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="18%">Tanggal</th>
            <th width="12%">Penjualan</th>
            <th width="12%">HPP</th>
            <th width="12%">Laba Kotor</th>
            <th width="14%">Pengeluaran</th>
            <th width="15%">Laba Bersih</th>
        </tr>
    </thead>

<tbody>
@foreach ($data as $index => $row)
    @if ($index + 1 < count($data))
        <tr>
            <td>{{ $row['DT_RowIndex'] }}</td>
            <td>{{ $row['tanggal'] }}</td>
            <td>{{ $row['penjualan'] }}</td>
            <td>{{ $row['hpp'] }}</td>
            <td>{{ $row['laba_kotor'] }}</td>
            <td>{{ $row['pengeluaran'] }}</td>
            <td>{{ $row['laba_bersih'] }}</td>
        </tr>
    @else
        <tr>
            <td colspan="6" class="text-right"><strong>{{ $row['pengeluaran'] }}</strong></td>
            <td><strong>{{ $row['laba_bersih'] }}</strong></td>
        </tr>
    @endif
@endforeach
</tbody>

    </table>
</body>
</html>