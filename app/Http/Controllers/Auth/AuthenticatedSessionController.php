<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Role;
use App\Models\UserOtp;
use App\Notifications\LoginOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Tentukan apakah role user boleh bypass OTP (RW, RT, Warga)
        $currentRoleId = $user->current_role_id;

        // Role ID berdasarkan RoleSeeder: RW = 35, RT = 36, Warga = 37
        $bypassOtpRoleIds = [35, 36, 37];

        if (in_array($currentRoleId, $bypassOtpRoleIds, true)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Untuk role selain RW/RT/Warga, wajib OTP
        // Generate OTP 6 digit
        $code = (string) random_int(100000, 999999);

        $expiresAt   = now()->addMinutes(10);
        $lastSentAt  = now();

        // Simpan / update OTP aktif untuk user ini
        UserOtp::updateOrCreate(
            [
                'user_id' => $user->id,
                'is_used' => false,
            ],
            [
                'code'         => $code,
                'expires_at'   => $expiresAt,
                'last_sent_at' => $lastSentAt,
                'attempts'     => 0,
            ]
        );

        // Kirim email OTP
        $user->notify(new LoginOtpNotification($code, 10));

        // Simpan informasi user yang pending OTP di session
        $request->session()->put('pending_otp_user_id', $user->id);
        $request->session()->put('pending_otp_remember', $request->boolean('remember'));

        // Logout dulu sampai OTP diverifikasi
        Auth::logout();

        // Jangan invalidate session agar pending_otp_* tetap ada

        return redirect()
            ->route('otp.show')
            ->with('status', 'Kode OTP telah dikirim ke email Anda. Silakan cek inbox atau folder spam.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
