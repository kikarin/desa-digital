<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssistanceProgram;
use App\Models\AssistanceRecipient;
use Carbon\Carbon;

class AutoUpdateProgramBantuanStatus extends Command
{
    protected $signature = 'program-bantuan:auto-update-status';
    protected $description = 'Auto update status program bantuan dan penerima yang sudah lewat jadwal';

    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');
        
        // Get semua program yang memiliki jadwal
        $programs = AssistanceProgram::whereNotNull('tanggal_penyaluran')
            ->whereIn('status', ['PROSES', 'PENYALURAN'])
            ->get();

        foreach ($programs as $program) {
            $tanggalPenyaluran = Carbon::parse($program->tanggal_penyaluran, 'Asia/Jakarta');
            
            // Tentukan jam mulai dan jam selesai
            $jamMulai = null;
            $jamSelesai = null;
            
            if ($program->jam_mulai_pengambilan) {
                $jamMulai = Carbon::parse($program->tanggal_penyaluran . ' ' . $program->jam_mulai_pengambilan, 'Asia/Jakarta');
            } else {
                $jamMulai = $tanggalPenyaluran->copy()->startOfDay();
            }
            
            if ($program->jam_selesai_pengambilan) {
                $jamSelesai = Carbon::parse($program->tanggal_penyaluran . ' ' . $program->jam_selesai_pengambilan, 'Asia/Jakarta');
            } else {
                $jamSelesai = $tanggalPenyaluran->copy()->endOfDay();
            }

            // Cek status berdasarkan waktu
            if ($now->gt($jamSelesai)) {
                // Sudah lewat jadwal -> SELESAI
                if ($program->status !== 'SELESAI') {
                    $program->update(['status' => 'SELESAI']);

                    // Update penerima yang masih PROSES jadi TIDAK_DATANG
                    $updated = AssistanceRecipient::where('assistance_program_id', $program->id)
                        ->where('status', 'PROSES')
                        ->whereNull('deleted_at')
                        ->update([
                            'status' => 'TIDAK_DATANG',
                            'updated_by' => null, // System update
                        ]);

                    $this->info("Program {$program->nama_program} (ID: {$program->id}) status updated to SELESAI. {$updated} penerima updated to TIDAK_DATANG");
                }
            } elseif ($now->gte($jamMulai) && $now->lte($jamSelesai)) {
                // Masih dalam jadwal -> PENYALURAN
                if ($program->status !== 'PENYALURAN') {
                    $program->update(['status' => 'PENYALURAN']);
                    $this->info("Program {$program->nama_program} (ID: {$program->id}) status updated to PENYALURAN");
                }
            } else {
                // Belum masuk jadwal -> PROSES
                if ($program->status !== 'PROSES') {
                    $program->update(['status' => 'PROSES']);
                    $this->info("Program {$program->nama_program} (ID: {$program->id}) status updated to PROSES");
                }
            }
        }

        return Command::SUCCESS;
    }
}
