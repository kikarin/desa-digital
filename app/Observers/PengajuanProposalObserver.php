<?php

namespace App\Observers;

use App\Models\PengajuanProposal;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class PengajuanProposalObserver
{
    private $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Handle the PengajuanProposal "updated" event.
     */
    public function updated(PengajuanProposal $proposal): void
    {
        // Cek apakah status berubah
        if ($proposal->wasChanged('status') && $proposal->created_by) {
            $statusLabel = PushNotificationService::formatStatus($proposal->status);
            $title = 'Status Proposal Diupdate';
            $body = "Proposal \"{$proposal->nama_kegiatan}\" berubah menjadi: {$statusLabel}";
            $url = "/services/proposal/{$proposal->id}";

            try {
                $this->pushService->sendToUser($proposal->created_by, $title, $body, $url);
            } catch (\Exception $e) {
                Log::error("Error kirim push notification untuk proposal ID {$proposal->id}: " . $e->getMessage());
            }
        }
    }
}
