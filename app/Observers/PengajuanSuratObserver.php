<?php

namespace App\Observers;

use App\Models\PengajuanSurat;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class PengajuanSuratObserver
{
    private $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Handle the PengajuanSurat "updated" event.
     */
    public function updated(PengajuanSurat $surat): void
    {
        // Cek apakah status berubah
        if ($surat->wasChanged('status') && $surat->created_by) {
            $statusLabel = PushNotificationService::formatStatus($surat->status);
            
            // Load relasi jenisSurat jika belum di-load
            if (!$surat->relationLoaded('jenisSurat')) {
                $surat->load('jenisSurat');
            }
            
            $jenisSuratNama = $surat->jenisSurat ? $surat->jenisSurat->nama : 'Surat';
            $title = 'Status Surat Diupdate';
            $body = "Pengajuan {$jenisSuratNama} berubah menjadi: {$statusLabel}";
            $url = "/services/letter/{$surat->id}";

            try {
                $this->pushService->sendToUser($surat->created_by, $title, $body, $url);
            } catch (\Exception $e) {
                Log::error("Error kirim push notification untuk surat ID {$surat->id}: " . $e->getMessage());
            }
        }
    }
}
