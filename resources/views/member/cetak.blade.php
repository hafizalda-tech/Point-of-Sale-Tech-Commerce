<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cetak Kartu Member</title>

    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .box {
            position: relative;
            width: 85.60mm;
            height: 53.98mm;
            overflow: hidden;
            background-color: #0D1B2A;
            border-radius: 8pt;
        }

        .background {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 0;
        }

        .logo {
            position: absolute;
            top: 5pt;
            left: 20pt;
            width: 100px; /* LOGO DIPERBESAR */
            z-index: 1;
        }

        .logo img {
            width: 100%;
        }

        .membercard-text {
            position: absolute;
            top: 20pt;
            left: 105pt; 
            font-size: 23pt; 
            font-weight: bold;
            font-family: Arial, Helvetica, sans-serif;
            color: #F8F4EC;
            line-height: 1.1;
            text-align: left;
            z-index: 1;
        }

        .namatoko {
            position: absolute;
            bottom: 50pt;
            left: 25pt;
            font-size: 13pt;
            font-weight: bold;
            color: #F8F4EC;
            font-family: Arial, Helvetica, sans-serif;
            z-index: 1;
        }

        .nama {
            position: absolute;
            bottom: 35pt;
            left: 25pt;
            font-size: 13pt;
            font-weight: bold;
            font-family: Arial, Helvetica, sans-serif;
            color: #E2A93B;
            z-index: 1;
        }

        .kode {
            position: absolute;
            bottom: 20pt;
            left: 25pt;
            font-size: 12pt;
            font-family: Arial, Helvetica, sans-serif;
            color: #F8F4EC;
            z-index: 1;
        }

        .barcode {
            position: absolute;
            bottom: 15pt;
            right: 25pt;
            background: #F8F4EC;
            padding: 4pt;
            border-radius: 4pt;
            z-index: 1;
        }

        .barcode img {
            height: 50px;
            width: 50px;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <section>
        <table width="100%">
            @foreach ($datamember as $key => $data)
                <tr>
                    @foreach ($data as $item)
                        <td class="text-center">
                            <div class="box">

                                @if (!empty($setting->path_kartu_member) && file_exists(public_path($setting->path_kartu_member)))
                                    <img src="{{ public_path($setting->path_kartu_member) }}" alt="card" class="background">
                                @endif

                                <!-- LOGO -->
                                <div class="logo">
                                    <img src="{{ public_path('img/logomember.png') }}" alt="logo">
                                </div>

                                <!-- MEMBER CARD -->
                                <div class="membercard-text">
                                    <div>MEMBER</div>
                                    <div>CARD</div>
                                </div>

                                <!-- INFO MEMBER -->
                                <div class="namatoko">{{ $setting->nama_perusahaan }}</div>
                                <div class="nama">{{ $item->nama }}</div>
                                <div class="kode">{{ $item->kode_member }}</div>

                                <!-- QR -->
                                <div class="barcode">
                                    <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($item->kode_member, 'QRCODE') }}" alt="qrcode">
                                </div>

                            </div>
                        </td>
                        @if (count($datamember) == 1)
                            <td class="text-center" style="width: 50%;"></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </table>
    </section>
</body>
</html>
