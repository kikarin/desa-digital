<?php

namespace App\Repositories;

use App\Traits\RepositoryTrait;
use App\Models\User;
use App\Models\Residents;
use App\Models\Families;
use App\Models\Houses;
use App\Models\Rws;
use App\Models\Rts;
use App\Models\PengajuanSurat;
use App\Models\PengajuanProposal;
use App\Models\AduanMasyarakat;
use App\Models\LayananDarurat;
use App\Models\AssistanceProgram;
use App\Models\AssistanceRecipient;
use App\Models\BeritaPengumuman;
use App\Models\BankSampah;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardRepository
{
    use RepositoryTrait;

    public function __construct()
    {
    }

    public function getStatistics()
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();
        
        // Data Warga
        $totalResidents = Residents::count();
        $residentsLastMonth = Residents::where('created_at', '<=', $lastMonth)->count();
        $residentsChange = $residentsLastMonth > 0 
            ? round((($totalResidents - $residentsLastMonth) / $residentsLastMonth) * 100, 1)
            : 0;

        $totalFamilies = Families::count();
        $familiesLastMonth = Families::where('created_at', '<=', $lastMonth)->count();
        $familiesChange = $familiesLastMonth > 0 
            ? round((($totalFamilies - $familiesLastMonth) / $familiesLastMonth) * 100, 1)
            : 0;

        $totalHouses = Houses::count();
        $housesLastMonth = Houses::where('created_at', '<=', $lastMonth)->count();
        $housesChange = $housesLastMonth > 0 
            ? round((($totalHouses - $housesLastMonth) / $housesLastMonth) * 100, 1)
            : 0;

        $totalRws = Rws::count();
        $totalRts = Rts::count();

        // Layanan Surat
        $totalPengajuanSurat = PengajuanSurat::count();
        $pengajuanSuratLastMonth = PengajuanSurat::where('created_at', '<=', $lastMonth)->count();
        $pengajuanSuratChange = $pengajuanSuratLastMonth > 0 
            ? round((($totalPengajuanSurat - $pengajuanSuratLastMonth) / $pengajuanSuratLastMonth) * 100, 1)
            : 0;

        $pengajuanSuratMenunggu = PengajuanSurat::where('status', 'menunggu')->count();
        $pengajuanSuratDisetujui = PengajuanSurat::where('status', 'disetujui')->count();
        $pengajuanSuratDitolak = PengajuanSurat::where('status', 'ditolak')->count();

        // Pengajuan Proposal
        $totalPengajuanProposal = PengajuanProposal::count();
        $pengajuanProposalLastMonth = PengajuanProposal::where('created_at', '<=', $lastMonth)->count();
        $pengajuanProposalChange = $pengajuanProposalLastMonth > 0 
            ? round((($totalPengajuanProposal - $pengajuanProposalLastMonth) / $pengajuanProposalLastMonth) * 100, 1)
            : 0;

        $pengajuanProposalMenunggu = PengajuanProposal::where('status', 'menunggu_verifikasi')->count();
        $pengajuanProposalDisetujui = PengajuanProposal::where('status', 'disetujui')->count();
        $pengajuanProposalDitolak = PengajuanProposal::where('status', 'ditolak')->count();

        // Aduan Masyarakat
        $totalAduan = AduanMasyarakat::count();
        $aduanLastMonth = AduanMasyarakat::where('created_at', '<=', $lastMonth)->count();
        $aduanChange = $aduanLastMonth > 0 
            ? round((($totalAduan - $aduanLastMonth) / $aduanLastMonth) * 100, 1)
            : 0;

        $aduanMenunggu = AduanMasyarakat::where('status', 'menunggu')->count();
        $aduanDiproses = AduanMasyarakat::where('status', 'diproses')->count();
        $aduanSelesai = AduanMasyarakat::where('status', 'selesai')->count();

        // Layanan Darurat
        $totalLayananDarurat = LayananDarurat::count();
        $layananDaruratLastMonth = LayananDarurat::where('created_at', '<=', $lastMonth)->count();
        $layananDaruratChange = $layananDaruratLastMonth > 0 
            ? round((($totalLayananDarurat - $layananDaruratLastMonth) / $layananDaruratLastMonth) * 100, 1)
            : 0;

        // Program Bantuan
        $totalProgramBantuan = AssistanceProgram::count();
        $totalPenerimaBantuan = AssistanceRecipient::count();
        $programBantuanLastMonth = AssistanceProgram::where('created_at', '<=', $lastMonth)->count();
        $programBantuanChange = $programBantuanLastMonth > 0 
            ? round((($totalProgramBantuan - $programBantuanLastMonth) / $programBantuanLastMonth) * 100, 1)
            : 0;

        // Berita & Pengumuman
        $totalBerita = BeritaPengumuman::count();
        $beritaLastMonth = BeritaPengumuman::where('created_at', '<=', $lastMonth)->count();
        $beritaChange = $beritaLastMonth > 0 
            ? round((($totalBerita - $beritaLastMonth) / $beritaLastMonth) * 100, 1)
            : 0;

        // Bank Sampah
        $totalBankSampah = BankSampah::count();
        $bankSampahLastMonth = BankSampah::where('created_at', '<=', $lastMonth)->count();
        $bankSampahChange = $bankSampahLastMonth > 0 
            ? round((($totalBankSampah - $bankSampahLastMonth) / $bankSampahLastMonth) * 100, 1)
            : 0;

        // Users
        $totalUsers = User::count();
        $usersLastMonth = User::where('created_at', '<=', $lastMonth)->count();
        $usersChange = $usersLastMonth > 0 
            ? round((($totalUsers - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : 0;

        return [
            'data_warga' => [
                'residents' => [
                    'total' => $totalResidents,
                    'change' => $residentsChange,
                    'trend' => $residentsChange >= 0 ? 'up' : 'down',
                ],
                'families' => [
                    'total' => $totalFamilies,
                    'change' => $familiesChange,
                    'trend' => $familiesChange >= 0 ? 'up' : 'down',
                ],
                'houses' => [
                    'total' => $totalHouses,
                    'change' => $housesChange,
                    'trend' => $housesChange >= 0 ? 'up' : 'down',
                ],
                'rws' => $totalRws,
                'rts' => $totalRts,
            ],
            'layanan_surat' => [
                'total' => $totalPengajuanSurat,
                'change' => $pengajuanSuratChange,
                'trend' => $pengajuanSuratChange >= 0 ? 'up' : 'down',
                'menunggu' => $pengajuanSuratMenunggu,
                'disetujui' => $pengajuanSuratDisetujui,
                'ditolak' => $pengajuanSuratDitolak,
            ],
            'pengajuan_proposal' => [
                'total' => $totalPengajuanProposal,
                'change' => $pengajuanProposalChange,
                'trend' => $pengajuanProposalChange >= 0 ? 'up' : 'down',
                'menunggu' => $pengajuanProposalMenunggu,
                'disetujui' => $pengajuanProposalDisetujui,
                'ditolak' => $pengajuanProposalDitolak,
            ],
            'aduan_masyarakat' => [
                'total' => $totalAduan,
                'change' => $aduanChange,
                'trend' => $aduanChange >= 0 ? 'up' : 'down',
                'menunggu' => $aduanMenunggu,
                'diproses' => $aduanDiproses,
                'selesai' => $aduanSelesai,
            ],
            'layanan_darurat' => [
                'total' => $totalLayananDarurat,
                'change' => $layananDaruratChange,
                'trend' => $layananDaruratChange >= 0 ? 'up' : 'down',
            ],
            'program_bantuan' => [
                'total_program' => $totalProgramBantuan,
                'total_penerima' => $totalPenerimaBantuan,
                'change' => $programBantuanChange,
                'trend' => $programBantuanChange >= 0 ? 'up' : 'down',
            ],
            'berita_pengumuman' => [
                'total' => $totalBerita,
                'change' => $beritaChange,
                'trend' => $beritaChange >= 0 ? 'up' : 'down',
            ],
            'bank_sampah' => [
                'total' => $totalBankSampah,
                'change' => $bankSampahChange,
                'trend' => $bankSampahChange >= 0 ? 'up' : 'down',
            ],
            'users' => [
                'total' => $totalUsers,
                'change' => $usersChange,
                'trend' => $usersChange >= 0 ? 'up' : 'down',
            ],
        ];
    }

    public function getRecentActivities($limit = 5)
    {
        $activities = DB::table('activity_log')
            ->select([
                'activity_log.*',
                'users.name as user_name',
                'users.file as user_file'
            ])
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->orderBy('activity_log.created_at', 'desc')
            ->limit($limit)
            ->get();

        return $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'description' => $activity->description,
                'subject_type' => $activity->subject_type,
                'event' => $activity->event,
                'user_name' => $activity->user_name ?? 'System',
                'user_file' => $activity->user_file,
                'created_at' => Carbon::parse($activity->created_at)->diffForHumans(),
                'created_at_full' => $activity->created_at,
            ];
        });
    }

    public function customIndex($data)
    {
        return $data;
    }
}
