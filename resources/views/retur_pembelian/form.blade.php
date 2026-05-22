<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form">
    <div class="modal-dialog modal-lg" role="document">
        <form action="" method="post" class="form-horizontal">
            @csrf
            @method('post')

            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"></h4> <!-- judul akan otomatis diisi dari JS -->
                </div>

                <div class="modal-body">
                <div class="form-group row">
                    <label class="col-lg-2 col-lg-offset-1 control-label">Supplier</label>
                    <div class="col-lg-6">
                        <select name="id_supplier" id="id_supplier" class="form-control" required>
                            <option value="">Pilih Supplier</option>
                            @foreach ($supplier as $row)
                                <option value="{{ $row->id_supplier }}">{{ $row->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="tanggal_pembelian" class="col-lg-2 col-lg-offset-1 control-label">Tanggal Pembelian</label>
                    <div class="col-lg-6">
                        <input type="text" name="tanggal_pembelian" id="tanggal_pembelian" class="form-control datepicker" required autofocus
                            value="{{ old('tanggal_pembelian') }}"
                            style="border-radius: 0 !important;">
                        <span class="help-block with-errors"></span>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-2 col-lg-offset-1 control-label">Pilih Produk</label>
                    <div class="col-lg-8">
                        <div id="produk-retur-wrapper">
                            <!-- Di sini akan ditampilkan list produk berdasarkan supplier dan tanggal -->
                        </div>
                    </div>
                </div>


                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary btn-flat"><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="btn btn-sm btn-warning btn-flat" data-dismiss="modal"><i class="fa fa-arrow-circle-left"></i> Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
