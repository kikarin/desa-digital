<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat - {{ $pengajuan->nomor_surat }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: normal;
        }
        .header {
            position: relative;
            width: 100%;
            margin-bottom: 10px;
            min-height: 80px;
        }
        .header-left {
            position: absolute;
            top: 0;
            left: 0;
        }
        .header-right {
            text-align: center;
            width: 100%;
        }
        .logo {
            width: 90px;
            height: auto;
        }
        .header-title {
            font-size: 15pt;
            font-weight: bold;
        }
        .header-subtitle {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-address {
            font-size: 10pt;
            margin-top: 3px;
        }
        .separator {
            border-top: 4px solid #000;
            margin: 10px 0 20px 0;
        }
        .title-surat {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            padding bottom: 4px;
            border-bottom: 2px solid #000;
        }
        .nomor-surat {
            text-align: center;
            margin-bottom: 20px;
            font-size: 11pt;
        }
        .content {
            text-align: justify;
            margin-bottom: 30px;
        }
        .data-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 8px 10px;
            vertical-align: top;
        }
        .data-table td:first-child {
            font-weight: bold;
            width: 30%;
        }
        .footer {
            margin-top: 60px;
            text-align: right;
        }
        .footer-location {
            font-size: 11pt;
        }
        .ttd-section {
            margin-top: 10px;
        }
        .ttd-image {
            max-width: 200px;
            max-height: 100px;
        }
        .ttd-name {
            font-weight: bold;
        }
        .ttd-role {
            font-size: 11pt;
        }
    </style>
</head>
<body>
    <!-- Header dengan Logo -->
    <div class="header">
        <div class="header-left">
            <img src="{{ storage_path('app/public/Lambang_Kabupaten_Bogor.png') }}" alt="Logo" class="logo">
        </div>
        <div class="header-right">
            <div class="header-title">PEMERINTAH KABUPATEN {{ strtoupper(config('desa.kabupaten', 'Bogor')) }}</div>
            <div class="header-title">KECAMATAN {{ strtoupper(config('desa.kecamatan', 'Cibungbulang')) }}</div>
            <div class="header-subtitle">{{ strtoupper(config('desa.nama_desa', 'Desa Galuga')) }}</div>
            <div class="header-address">
                Jl. Galuga, RT. 002/001, Kode Pos: 16630<br>
                email: dsgaluga@gmail.com<br>
                Provinsi {{ config('desa.provinsi', 'Jawa Barat') }}
            </div>
        </div>
    </div>

    <!-- Separator -->
    <div class="separator"></div>

    <!-- Title Surat (Nama Jenis Surat) -->
    <div style="text-align: center; margin-bottom: 5px;">
        <div class="title-surat">
            {{ $pengajuan->jenisSurat->nama ?? '-' }}
        </div>
    </div>

    <!-- Nomor Surat -->
    <div class="nomor-surat">
        {{ $pengajuan->nomor_surat ?? '-' }}<br>
    </div>

    <!-- Isi Surat -->
    <div class="content">
        <p style="text-indent: 50px; margin-bottom: 15px;">
            Yang bertanda tangan di bawah ini, Kepala {{ config('desa.nama_desa', 'Desa Galuga') }}, 
            Kecamatan {{ config('desa.kecamatan', 'Cibungbulang') }}, 
            Kabupaten {{ config('desa.kabupaten', 'Bogor') }}, dengan ini menerangkan bahwa:
        </p>

        <!-- Data Atribut -->
        @if($atribut_detail && count($atribut_detail) > 0)
            <table class="data-table">
                @foreach($atribut_detail as $atribut)
                    <tr>
                        <td>{{ $atribut['atribut_nama'] }}</td>
                        <td>: {{ $atribut['nilai'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <p style="text-indent: 50px; margin-top: 20px;">
            Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <!-- Footer: Tanda Tangan -->
    <div class="footer">
        <div class="footer-location">
            <strong>
            {{ config('desa.nama_desa', 'Desa Galuga') }}, 
            {{ $pengajuan->tanggal_disetujui ? \Carbon\Carbon::parse($pengajuan->tanggal_disetujui)->locale('id')->isoFormat('D MMMM YYYY') : '-' }}
            </strong>
        </div>
                @php
                    $userRole = $pengajuan->adminVerifikasi->role ?? null;
                @endphp
                @if($userRole)
                    <div class="ttd-role">
                        <strong>An. {{ $userRole->name }}</strong>
                    </div>
                @endif
        <div class="ttd-section">
            @if($pengajuan->tanda_tangan_type === 'digital' && $pengajuan->tanda_tangan_digital)
                <img src="{{ $pengajuan->tanda_tangan_digital }}" alt="Tanda Tangan Digital" class="ttd-image">
            @elseif($pengajuan->tanda_tangan_type === 'foto' && $pengajuan->foto_tanda_tangan)
                <img src="{{ storage_path('app/public/' . $pengajuan->foto_tanda_tangan) }}" alt="Foto Tanda Tangan" class="ttd-image">
            @endif

            @if($pengajuan->adminVerifikasi)
                <div class="ttd-name">
                    {{ $pengajuan->adminVerifikasi->name }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>

