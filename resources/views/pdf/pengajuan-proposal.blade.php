<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pengajuan Proposal - {{ $pengajuan->nama_kegiatan }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
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
            margin-bottom: 20px;
            padding-bottom: 4px;
            border-bottom: 2px solid #000;
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
            border-bottom: 1px solid #ddd;
        }
        .data-table td:first-child {
            font-weight: bold;
            width: 35%;
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
            margin-top: 5px;
        }
        .ttd-role {
            font-size: 11pt;
        }
        .section-title {
            font-weight: bold;
            font-size: 13pt;
            margin-top: 20px;
            margin-bottom: 10px;
            text-decoration: underline;
        }
        .info-box {
            background-color: #f5f5f5;
            padding: 15px;
            border: 1px solid #ddd;
            margin: 15px 0;
            border-radius: 5px;
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

    <!-- Title Surat -->
    <div class="title-surat">
        Pengajuan Proposal Kegiatan
    </div>

    <!-- Isi Surat -->
    <div class="content">
        <p style="text-indent: 50px; margin-bottom: 15px;">
            Yang bertanda tangan di bawah ini, Kepala {{ config('desa.nama_desa', 'Desa Galuga') }}, 
            Kecamatan {{ config('desa.kecamatan', 'Cibungbulang') }}, 
            Kabupaten {{ config('desa.kabupaten', 'Bogor') }}, dengan ini menyetujui pengajuan proposal kegiatan sebagai berikut:
        </p>

        <!-- Data Proposal -->
        <div class="info-box">
            <table class="data-table">
                <tr>
                    <td>Kategori Proposal</td>
                    <td>: {{ $pengajuan->kategoriProposal->nama ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Nama Pemohon</td>
                    <td>: {{ $pengajuan->resident->nama ?? '-' }} ({{ $pengajuan->resident->nik ?? '-' }})</td>
                </tr>
                <tr>
                    <td>Nama Kegiatan</td>
                    <td>: {{ $pengajuan->nama_kegiatan }}</td>
                </tr>
                <tr>
                    <td>Deskripsi Kegiatan</td>
                    <td>: {{ $pengajuan->deskripsi_kegiatan }}</td>
                </tr>
                <tr>
                    <td>Usulan Anggaran</td>
                    <td>: Rp {{ number_format($pengajuan->usulan_anggaran, 0, ',', '.') }}</td>
                </tr>
                @if($pengajuan->kecamatan_id || $pengajuan->desa_id || $pengajuan->kecamatan || $pengajuan->kelurahan_desa)
                <tr>
                    <td>Lokasi Kegiatan</td>
                    <td>: 
                        @if($pengajuan->kecamatan_id && $pengajuan->kecamatan)
                            Kecamatan {{ $pengajuan->kecamatan->nama }}
                        @elseif($pengajuan->kecamatan)
                            Kecamatan {{ $pengajuan->kecamatan }}
                        @endif
                        @if($pengajuan->desa_id && $pengajuan->desa)
                            , {{ $pengajuan->desa->nama }}
                        @elseif($pengajuan->kelurahan_desa)
                            , {{ $pengajuan->kelurahan_desa }}
                        @endif
                        @if($pengajuan->deskripsi_lokasi_tambahan)
                            <br>{{ $pengajuan->deskripsi_lokasi_tambahan }}
                        @endif
                    </td>
                </tr>
                @endif
                @if($pengajuan->latitude && $pengajuan->longitude)
                <tr>
                    <td>Koordinat Lokasi</td>
                    <td>: {{ $pengajuan->latitude }}, {{ $pengajuan->longitude }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p style="text-indent: 50px; margin-top: 20px;">
            Proposal kegiatan ini telah disetujui dan dapat dilaksanakan sesuai dengan ketentuan yang berlaku.
        </p>

        @if($pengajuan->catatan_verifikasi)
        <div class="section-title">Catatan Verifikasi:</div>
        <p style="text-indent: 50px; margin-top: 10px;">
            {{ $pengajuan->catatan_verifikasi }}
        </p>
        @endif
    </div>

    <!-- Footer: Tanda Tangan -->
    <div class="footer">
        <div class="footer-location">
            <strong>
            {{ config('desa.nama_desa', 'Desa Galuga') }}, 
            {{ $pengajuan->tanggal_diverifikasi ? \Carbon\Carbon::parse($pengajuan->tanggal_diverifikasi)->locale('id')->isoFormat('D MMMM YYYY') : '-' }}
            </strong>
        </div>
        <div class="ttd-section">
            @if($pengajuan->tanda_tangan_digital)
                <img src="{{ $pengajuan->tanda_tangan_digital }}" alt="Tanda Tangan Digital" class="ttd-image">
            @endif

            @if($pengajuan->adminVerifikasi)
                <div class="ttd-name">
                    {{ $pengajuan->adminVerifikasi->name }}
                </div>
                <div class="ttd-role">
                    Kepala {{ config('desa.nama_desa', 'Desa Galuga') }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>

