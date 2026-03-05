<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    /**
     * Subscribe user untuk push notification
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|url|max:500',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subscription data',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $endpoint = $request->input('endpoint');
            $keys = $request->input('keys');

            // Cek apakah subscription sudah ada
            $subscription = PushSubscription::where('user_id', $user->id)
                ->where('endpoint', $endpoint)
                ->first();

            if ($subscription) {
                // Update subscription yang sudah ada
                $subscription->update([
                    'public_key' => $keys['p256dh'],
                    'auth_token' => $keys['auth'],
                    'keys' => $keys,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription updated successfully',
                ]);
            }

            // Buat subscription baru
            PushSubscription::create([
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'public_key' => $keys['p256dh'],
                'auth_token' => $keys['auth'],
                'keys' => $keys,
            ]);

            Log::info("User ID {$user->id} berhasil subscribe push notification");

            return response()->json([
                'success' => true,
                'message' => 'Subscription saved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error saat subscribe push notification: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unsubscribe user dari push notification
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid endpoint',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $endpoint = $request->input('endpoint');

            PushSubscription::where('user_id', $user->id)
                ->where('endpoint', $endpoint)
                ->delete();

            Log::info("User ID {$user->id} berhasil unsubscribe push notification");

            return response()->json([
                'success' => true,
                'message' => 'Subscription removed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error saat unsubscribe push notification: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove subscription',
            ], 500);
        }
    }

    /**
     * Get VAPID public key untuk frontend
     */
    public function getVapidPublicKey()
    {
        $publicKey = config('webpush.vapid.public_key');

        if (!$publicKey) {
            return response()->json([
                'success' => false,
                'message' => 'VAPID public key tidak dikonfigurasi',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'public_key' => $publicKey,
        ]);
    }
}
