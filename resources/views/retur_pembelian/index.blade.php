@extends('layouts.master')

@section('title')
    Daftar Retur Pembelian
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('/AdminLTE-2/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}">
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Retur Pembelian</li>
@endsection

@section('content')
<div class="box">
    <div class="box-header with-border">
        <button onclick="addForm('{{ route('retur_pembelian.store') }}')" class="btn btn-success btn-xs btn-flat">
            <i class="fa fa-plus-circle"></i> Tambah Retur
        </button>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-retur">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Supplier</th>
                    <th>Tanggal Retur</th>
                    <th>Tanggal Pembelian</th>
                    <th>Total Produk</th>
                    <th>Total Item</th>
                    <th width="15%"><i class="fa fa-cog"></i></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@includeIf('retur_pembelian.detail')
@includeIf('retur_pembelian.form')
@endsection

@push('scripts')
<script src="{{ asset('/AdminLTE-2/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script>
let table, tableDetail;

$(function () {
    table = $('.table-retur').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('retur_pembelian.data') }}',
        columns: [
            {data: 'DT_RowIndex', searchable: false, sortable: false},
            {data: 'supplier'},
            {
                data: 'tanggal_retur',
                render: function(data) {
                    return new Date(data).toLocaleDateString('id-ID', {
                        day: '2-digit', month: 'long', year: 'numeric'
                    });
                }
            },
            {
                data: 'tanggal_pembelian',
                render: function(data) {
                    return new Date(data).toLocaleDateString('id-ID', {
                        day: '2-digit', month: 'long', year: 'numeric'
                    });
                }
            },
            {data: 'total_produk'},
            {data: 'total_item'},
            {data: 'aksi', searchable: false, sortable: false},
        ]
    });

    tableDetail = $('.table-detail').DataTable({
        processing: true,
        bSort: false,
        dom: 'Brt',
        ajax: '',
        columns: [
            {data: 'DT_RowIndex', searchable: false, sortable: false},
            {data: 'kode_produk'},
            {data: 'nama_produk'},
            {data: 'merk'},
            {data: 'jumlah'},
            {data: 'alasan'},
        ]
    });

    $('#modal-form').validator().on('submit', function (e) {
        if (!e.isDefaultPrevented()) return true;
    });

    $('#modal-form').on('hidden.bs.modal', function () {
        $('#produk-retur-wrapper').html('');
        $('#id_supplier').val('');
        $('#tanggal_pembelian').val('');
    });

    $('#id_supplier, #tanggal_pembelian').on('change', function () {
        let idSupplier = $('#id_supplier').val();
        let rawTanggal = $('#tanggal_pembelian').val();
        if (idSupplier && rawTanggal) {
            let tanggal = new Date(rawTanggal).toISOString().slice(0, 10);
            $.get(`{{ route('retur_pembelian.load_produk') }}?supplier=${idSupplier}&tanggal=${tanggal}`, function (res) {
                $('#produk-retur-wrapper').html(res);
            }).fail(function () {
                $('#produk-retur-wrapper').html('<p class="text-danger text-center">Tidak ada produk ditemukan.</p>');
            });
        } else {
            $('#produk-retur-wrapper').html('');
        }
    });

    // Inisialisasi datepicker saat modal ditampilkan
    $('#modal-form').on('shown.bs.modal', function () {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        }).datepicker('update');
    });
});

function addForm(url) {
    $('#modal-form').modal('show');
    $('#modal-form .modal-title').text('Tambah Retur Pembelian');
    $('#modal-form form')[0].reset();
    $('#modal-form form').attr('action', url);
    $('#modal-form [name=_method]').val('post');
}

function deleteData(url) {
    if (confirm('Yakin ingin menghapus data terpilih?')) {
        $.post(url, {
            '_token': '{{ csrf_token() }}',
            '_method': 'delete'
        })
        .done(() => {
            table.ajax.reload();
        })
        .fail(() => {
            alert('Tidak dapat menghapus data');
        });
    }
}

function showDetail(url) {
    $('#modal-detail').modal('show');
    tableDetail.ajax.url(url);
    tableDetail.ajax.reload();
}

$('#modal-form form').on('submit', function (e) {
    let valid = true;
    let warning = "";

    $('input[name^="jumlah_retur"]').each(function () {
        const input = $(this);
        const jumlah = parseInt(input.val());
        const id = input.attr('name').match(/\d+/)[0];
        const checkbox = $(`input[name="produk_id[${id}]"]`);

        if (jumlah && jumlah > 0 && !checkbox.is(':checked')) {
            valid = false;
            const namaProduk = checkbox.closest('tr').find('td:nth-child(2)').text().trim();
            warning += `\n- ${namaProduk}`;
        }
    });

    if (!valid) {
        e.preventDefault();
        alert("Mohon centang produk yang ingin diretur:" + warning);
    }
});
</script>
@endpush
