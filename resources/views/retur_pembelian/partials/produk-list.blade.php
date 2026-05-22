@if ($produk->isEmpty())
    <p class="text-center text-danger">Tidak ada produk dari supplier & tanggal tersebut.</p>
@else
    <table class="table table-bordered table-striped table-sm text-center">
        <thead class="thead-light">
            <tr>
                <th style="width: 5%">Pilih</th>
                <th style="width: 25%">Nama</th>
                <th style="width: 15%">Merk</th>
                <th style="width: 10%">Jumlah</th>
                <th style="width: 15%">Retur</th>
                <th style="width: 30%">Alasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($produk as $item)
                <tr class="{{ $item->jumlah_sisa < $item->jumlah ? 'table-warning' : '' }}">
                    <td>
                        <input type="checkbox" name="produk_id[{{ $item->produk->id_produk }}]" value="{{ $item->produk->id_produk }}">
                    </td>
                    <td class="text-left">{{ $item->produk->nama_produk }}</td>
                    <td>{{ $item->produk->merk }}</td>
                    <td>{{ $item->jumlah_sisa }}</td>
                    <td>
                        <input type="number" name="jumlah_retur[{{ $item->produk->id_produk }}]"
                               class="form-control form-control-sm text-center"
                               min="1" max="{{ $item->jumlah_sisa }}">
                    </td>
                    <td>
                        <input type="text" name="alasan[{{ $item->produk->id_produk }}]"
                               class="form-control form-control-sm"
                               placeholder="Opsional...">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
