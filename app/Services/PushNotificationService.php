<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private $webPush;

    public function __construct()
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        $subject = config('webpush.vapid.subject');

        if (!$publicKey || !$privateKey) {
            throw new \Exception('VAPID keys tidak dikonfigurasi. Pastikan VAPID_PUBLIC_KEY dan VAPID_PRIVATE_KEY sudah di-set di .env');
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    /**
     * Kirim push notification ke user tertentu
     */
    public function sendToUser(int $userId, string $title, string $body, string $url = '/')
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            Log::info("Tidak ada subscription untuk user ID: {$userId}");
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/icon-192x192.png', 
            'badge' => '/icon-192x192.png',
            'url' => $url,
            'data' => [
                'url' => $url,
            ],
        ]);

        $successCount = 0;
        $failedSubscriptions = [];

        foreach ($subscriptions as $subscription) {
            try {
                $pushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->public_key ?? $subscription->keys['p256dh'] ?? '',
                        'auth' => $subscription->auth_token ?? $subscription->keys['auth'] ?? '',
                    ],
                ]);

                $result = $this->webPush->sendOneNotification(
                    $pushSubscription,
                    $payload
                );

                if ($result->isSuccess()) {
                    $successCount++;
                    Log::info("Push notification berhasil dikirim ke user ID: {$userId}, endpoint: " . substr($subscription->endpoint, 0, 50) . '...');
                } else {
                    // Handle expired/invalid subscription
                    if ($result->isSubscriptionExpired() || $result->getStatusCode() === 410) {
                        $failedSubscriptions[] = $subscription->id;
                        Log::warning("Subscription expired untuk user ID: {$userId}, endpoint: " . substr($subscription->endpoint, 0, 50) . '...');
                    } else {
                        Log::error("Gagal kirim push notification ke user ID: {$userId}, error: " . $result->getReason());
                    }
                }
            } catch (\Exception $e) {
                Log::error("Exception saat kirim push notification ke user ID: {$userId}: " . $e->getMessage());
                $failedSubscriptions[] = $subscription->id;
            }
        }

        // Hapus subscription yang expired/invalid
        if (!empty($failedSubscriptions)) {
            PushSubscription::whereIn('id', $failedSubscriptions)->delete();
            Log::info("Menghapus " . count($failedSubscriptions) . " subscription yang expired/invalid");
        }

        return [
            'success_count' => $successCount,
            'failed_count' => count($failedSubscriptions),
            'total' => $subscriptions->count(),
        ];
    }

    /**
     * Format status menjadi teks yang mudah dibaca
     */
    public static function formatStatus(string $status): string
    {
        $statusMap = [
            // Aduan Masyarakat
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            
            // Pengajuan Proposal
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            
            // Pengajuan Surat
            'menunggu' => 'Menunggu',
            'diperbaiki' => 'Diperbaiki',
        ];

        return $statusMap[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
