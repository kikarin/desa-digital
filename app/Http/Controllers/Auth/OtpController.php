<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOtp;
use App\Notifications\LoginOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    /**
     * Tampilkan halaman input OTP.
     */
    public function show(Request $request): RedirectResponse|Response
    {
        $pendingUserId = $request->session()->get('pending_otp_user_id');

        if (! $pendingUserId) {
            return redirect()
                ->route('login')
                ->with('status', 'Sesi OTP tidak ditemukan atau sudah kedaluwarsa. Silakan login kembali.');
        }

        $user = User::find($pendingUserId);

        if (! $user) {
            $request->session()->forget(['pending_otp_user_id', 'pending_otp_remember']);

            return redirect()
                ->route('login')
                ->with('status', 'Akun tidak ditemukan. Silakan login kembali.');
        }

        return Inertia::render('auth/OtpVerification', [
            'status' => $request->session()->get('status'),
            'email'  => $user->email,
        ]);
    }

    /**
     * Verifikasi kode OTP yang dimasukkan user.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $pendingUserId = $request->session()->get('pending_otp_user_id');

        if (! $pendingUserId) {
            return redirect()
                ->route('login')
                ->with('status', 'Sesi OTP tidak ditemukan atau sudah kedaluwarsa. Silakan login kembali.');
        }

        $user = User::find($pendingUserId);

        if (! $user) {
            $request->session()->forget(['pending_otp_user_id', 'pending_otp_remember']);

            return redirect()
                ->route('login')
                ->with('status', 'Akun tidak ditemukan. Silakan login kembali.');
        }

        /** @var UserOtp|null $otp */
        $otp = UserOtp::where('user_id', $user->id)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (! $otp) {
            return redirect()
                ->back()
                ->withErrors(['code' => 'Kode OTP tidak ditemukan. Silakan kirim ulang kode.']);
        }

        // Cek kadaluarsa
        if ($otp->isExpired()) {
            return redirect()
                ->back()
                ->withErrors(['code' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode.']);
        }

        // (Opsional) batasi jumlah percobaan
        if ($otp->attempts >= 5) {
            return redirect()
                ->route('login')
                ->with('status', 'Percobaan OTP sudah melebihi batas. Silakan login kembali.');
        }

        if ($otp->code !== $request->input('code')) {
            $otp->increment('attempts');

            return redirect()
                ->back()
                ->withErrors(['code' => 'Kode OTP yang Anda masukkan salah.']);
        }

        // Berhasil
        $otp->update([
            'is_used' => true,
        ]);

        $remember = (bool) $request->session()->pull('pending_otp_remember', false);
        $request->session()->forget('pending_otp_user_id');

        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()
            ->intended(route('dashboard', absolute: false));
    }

    /**
     * Kirim ulang kode OTP dengan jeda minimal 1 menit.
     */
    public function resend(Request $request): RedirectResponse
    {
        $pendingUserId = $request->session()->get('pending_otp_user_id');

        if (! $pendingUserId) {
            return redirect()
                ->route('login')
                ->with('status', 'Sesi OTP tidak ditemukan atau sudah kedaluwarsa. Silakan login kembali.');
        }

        $user = User::find($pendingUserId);

        if (! $user) {
            $request->session()->forget(['pending_otp_user_id', 'pending_otp_remember']);

            return redirect()
                ->route('login')
                ->with('status', 'Akun tidak ditemukan. Silakan login kembali.');
        }

        /** @var UserOtp|null $otp */
        $otp = UserOtp::where('user_id', $user->id)
            ->where('is_used', false)
            ->latest()
            ->first();

        $now = now();

        if ($otp && $otp->last_sent_at && $otp->last_sent_at->diffInSeconds($now) < 60) {
            return redirect()
                ->back()
                ->with('status', 'Kode OTP sudah dikirim. Silakan tunggu 1 menit sebelum meminta kode baru.');
        }

        // Generate kode baru
        $code       = (string) random_int(100000, 999999);
        $expiresAt  = $now->clone()->addMinutes(10);

        if (! $otp) {
            $otp = new UserOtp();
            $otp->user_id = $user->id;
        }

        $otp->code         = $code;
        $otp->expires_at   = $expiresAt;
        $otp->last_sent_at = $now;
        $otp->attempts     = 0;
        $otp->is_used      = false;
        $otp->save();

        $user->notify(new LoginOtpNotification($code, 10));

        return redirect()
            ->back()
            ->with('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }
}

