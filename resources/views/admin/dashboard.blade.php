@extends('layouts.master')

@section('title')
    Dashboard
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Dashboard</li>
@endsection

@section('content')
<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $kategori }}</h3>

                <p>Total Kategori</p>
            </div>
            <div class="icon">
                <i class="fa fa-cube"></i>
            </div>
            <a href="{{ route('kategori.index') }}" class="small-box-footer">Lihat <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-green">
            <div class="inner">
                <h3>{{ $produk }}</h3>

                <p>Total Produk</p>
            </div>
            <div class="icon">
                <i class="fa fa-cubes"></i>
            </div>
            <a href="{{ route('produk.index') }}" class="small-box-footer">Lihat <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $member }}</h3>

                <p>Total Member</p>
            </div>
            <div class="icon">
                <i class="fa fa-id-card"></i>
            </div>
            <a href="{{ route('member.index') }}" class="small-box-footer">Lihat <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-6">
        <!-- small box -->
        <div class="small-box bg-red">
            <div class="inner">
                <h3>{{ $supplier }}</h3>

                <p>Total Supplier</p>
            </div>
            <div class="icon">
                <i class="fa fa-truck"></i>
            </div>
            <a href="{{ route('supplier.index') }}" class="small-box-footer">Lihat <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
</div>
<!-- /.row -->

<!-- Main row -->
<div class="row">
        <div class="col-md-12">
          <div class="box">
            <div class="box-header with-border">
              <h3 class="box-title">Daily Recap Report</h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
                <div class="btn-group">
                  <button type="button" class="btn btn-box-tool dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-wrench"></i></button>
                  <ul class="dropdown-menu" role="menu">
                    <li><a href="#">Action</a></li>
                    <li><a href="#">Another action</a></li>
                    <li><a href="#">Something else here</a></li>
                    <li class="divider"></li>
                    <li><a href="#">Separated link</a></li>
                  </ul>
                </div>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button>
              </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <div class="row">
                <div class="col-md-8">
                  <p class="text-center">
                    <strong>{{ tanggal_indonesia($tanggal_awal, false) }} s/d {{ tanggal_indonesia($tanggal_akhir, false) }}</strong>
                  </p>

                  <div class="chart">
                    <!-- Sales Chart Canvas -->
                    <canvas id="salesChart" style="height: 180px;"></canvas>
                  </div>
                  <!-- /.chart-responsive -->
                </div>
                <!-- /.col -->
                <div class="col-md-4">
                    <p class="text-center">
                        <strong>Kategori Produk Terlaris</strong>
                    </p>

                    @php
                        $warna_kategori = ['progress-bar-aqua', 'progress-bar-red', 'progress-bar-green', 'progress-bar-yellow'];
                    @endphp

                    @foreach ($kategori_terlaris as $index => $kategori)
                        @php
                            $target = 50; // Target default
                            $persentase = ($kategori->total_terjual / $target) * 100;
                            $warna = $warna_kategori[$index % count($warna_kategori)]; // Ambil warna sesuai urutan
                        @endphp

                        <div class="progress-group">
                            <span class="progress-text">{{ $kategori->nama_kategori }}</span>
                            <span class="progress-number"><b>{{ $kategori->total_terjual }}</b></span>

                            <div class="progress sm">
                                <div class="progress-bar {{ $warna }}" style="width: {{ min($persentase, 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
            </div>
            <!-- ./box-body -->

            <div class="box-footer">
              <div class="row">

                <!-- PENJUALAN -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                    <span class="description-percentage 
                      {{ $persentase_penjualan > 0 ? 'text-green' : ($persentase_penjualan < 0 ? 'text-red' : 'text-yellow') }}">
                      <i class="fa 
                        {{ $persentase_penjualan > 0 ? 'fa-caret-up' : ($persentase_penjualan < 0 ? 'fa-caret-down' : 'fa-caret-left') }}">
                      </i> 
                      {{ number_format(abs($persentase_penjualan), 0) }}%
                    </span>
                    <h5 class="description-header">Rp {{ number_format($total_penjualan, 0, ',', '.') }}</h5>
                    <span class="description-text">PENJUALAN</span>
                  </div>
                </div>

                <!-- HPP -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                    <span class="description-percentage 
                      {{ $persentase_hpp > 0 ? 'text-red' : ($persentase_hpp < 0 ? 'text-green' : 'text-yellow') }}">
                      <i class="fa 
                        {{ $persentase_hpp > 0 ? 'fa-caret-up' : ($persentase_hpp < 0 ? 'fa-caret-down' : 'fa-caret-left') }}">
                      </i> 
                      {{ number_format(abs($persentase_hpp), 0) }}%
                    </span>
                    <h5 class="description-header">Rp {{ number_format($total_hpp, 0, ',', '.') }}</h5>
                    <span class="description-text">HPP</span>
                  </div>
                </div>

                <!-- PENGELUARAN -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block border-right">
                    <span class="description-percentage 
                      {{ $persentase_pengeluaran > 0 ? 'text-red' : ($persentase_pengeluaran < 0 ? 'text-green' : 'text-yellow') }}">
                      <i class="fa 
                        {{ $persentase_pengeluaran > 0 ? 'fa-caret-up' : ($persentase_pengeluaran < 0 ? 'fa-caret-down' : 'fa-caret-left') }}">
                      </i> 
                      {{ number_format(abs($persentase_pengeluaran), 0) }}%
                    </span>
                    <h5 class="description-header">Rp {{ number_format($total_pengeluaran, 0, ',', '.') }}</h5>
                    <span class="description-text">PENGELUARAN</span>
                  </div>
                </div>

                <!-- LABA BERSIH -->
                <div class="col-sm-3 col-xs-6">
                  <div class="description-block">
                    <span class="description-percentage 
                      {{ $persentase_laba > 0 ? 'text-green' : ($persentase_laba < 0 ? 'text-red' : 'text-yellow') }}">
                      <i class="fa 
                        {{ $persentase_laba > 0 ? 'fa-caret-up' : ($persentase_laba < 0 ? 'fa-caret-down' : 'fa-caret-left') }}">
                      </i> 
                      {{ number_format(abs($persentase_laba), 0) }}%
                    </span>
                    <h5 class="description-header">Rp {{ number_format($total_laba_bersih, 0, ',', '.') }}</h5>
                    <span class="description-text">LABA BERSIH</span>
                  </div>
                </div>

              </div>
            </div>


            <!-- /.box-footer -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
<!-- /.row (main row) -->
@endsection

@push('scripts')
<!-- ChartJS -->
<script src="{{ asset('AdminLTE-2/bower_components/chart.js/Chart.js') }}"></script>
<script>
$(function() {
    // Get context with jQuery - using jQuery's .get() method.
    var salesChartCanvas = $('#salesChart').get(0).getContext('2d');
    // This will get the first returned node in the jQuery collection.
    var salesChart = new Chart(salesChartCanvas);

    var salesChartData = {
        labels: {{ json_encode($data_tanggal) }},
        datasets: [
            {
                label: 'Pendapatan {{ \Carbon\Carbon::now()->subMonth()->translatedFormat("F") }}',
                fillColor           : 'rgba(200, 200, 200, 0.7)',
                strokeColor         : 'rgba(200, 200, 200, 0.7)',
                pointColor          : '#b0b0b0',
                pointStrokeColor    : 'rgba(170, 170, 170,1)',
                pointHighlightFill  : '#fff',
                pointHighlightStroke: 'rgba(170, 170, 170,1)',
                data: {{ json_encode($data_pendapatan_bulan_lalu) }}
            },
            {
                label: 'Pendapatan {{ \Carbon\Carbon::now()->translatedFormat("F") }}',
                fillColor           : 'rgba(60,141,188,0.9)',
                strokeColor         : 'rgba(60,141,188,0.8)',
                pointColor          : '#3b8bba',
                pointStrokeColor    : 'rgba(60,141,188,1)',
                pointHighlightFill  : '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: {{ json_encode($data_pendapatan) }}
            }
        ]
    };

    function formatRibuan(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    var salesChartOptions = {
        pointDot: false,
        responsive: true,
        tooltipTitleTemplate: "Tanggal <%= label %>",

        multiTooltipTemplate: function(label) {
        return label.datasetLabel + ': ' + formatRibuan(label.value);
        },

        scaleLabel: function(label) {
            return formatRibuan(label.value);
        }
    };

    salesChart.Line(salesChartData, salesChartOptions);
});
</script>
@endpush
