<?php

namespace App\Observers;

use App\Models\AduanMasyarakat;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class AduanMasyarakatObserver
{
    private $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Handle the AduanMasyarakat "updated" event.
     */
    public function updated(AduanMasyarakat $aduan): void
    {
        // Cek apakah status berubah
        if ($aduan->wasChanged('status') && $aduan->created_by) {
            $statusLabel = PushNotificationService::formatStatus($aduan->status);
            $title = 'Status Aduan Diupdate';
            $body = "Aduan \"{$aduan->judul}\" berubah menjadi: {$statusLabel}";
            $url = "/services/aduan/complaint/masyarakat/{$aduan->id}";

            try {
                $this->pushService->sendToUser($aduan->created_by, $title, $body, $url);
            } catch (\Exception $e) {
                Log::error("Error kirim push notification untuk aduan ID {$aduan->id}: " . $e->getMessage());
            }
        }
    }
}
