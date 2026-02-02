<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    private GeminiService $geminiService;
    private const CACHE_TTL = 86400; 
    private const RATE_LIMIT_PER_HOUR = 50;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Handle chat request
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_history' => 'nullable|array',
            'conversation_history.*.role' => 'required_with:conversation_history|string|in:user,assistant',
            'conversation_history.*.content' => 'required_with:conversation_history|string',
        ]);

        $userMessage = trim($request->message);
        $userId = auth()->id() ?? 'guest';

        $rateLimitKey = "chatbot:rate_limit:{$userId}";
        $requests = Cache::get($rateLimitKey, 0);
        
        if ($requests >= self::RATE_LIMIT_PER_HOUR) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.',
                'retry_after' => 3600
            ], 429);
        }

        $normalizedMessage = $this->normalizeMessage($userMessage);
        $cacheKey = 'chatbot:response:' . md5($normalizedMessage);

        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse && empty($request->conversation_history)) {
            Log::info('Chatbot: Cache hit', ['user_id' => $userId, 'message' => substr($normalizedMessage, 0, 50)]);
            return response()->json([
                'success' => true,
                'message' => $cachedResponse,
                'from_cache' => true
            ]);
        }

        try {
            Cache::put($rateLimitKey, $requests + 1, 3600);

            $systemContext = $this->getSystemContext();

            $response = $this->geminiService->chat(
                $userMessage,
                $request->conversation_history ?? [],
                $systemContext
            );

            if (empty($request->conversation_history) && strlen($response) > 20) {
                Cache::put($cacheKey, $response, self::CACHE_TTL);
            }

            Log::info('Chatbot: Success', [
                'user_id' => $userId,
                'message_length' => strlen($userMessage),
                'response_length' => strlen($response)
            ]);

            return response()->json([
                'success' => true,
                'message' => $response,
                'from_cache' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot Error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'message' => substr($userMessage, 0, 100)
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->getFallbackResponse($userMessage, $e->getMessage()),
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get quick suggestions for common questions
     */
    public function suggestions(): JsonResponse
    {
        $suggestions = [
            [
                'category' => 'Data Warga',
                'questions' => [
                    'Bagaimana cara menambah data warga baru?',
                    'Bagaimana cara edit data keluarga?',
                    'Apa saja field yang wajib diisi untuk data rumah?',
                ]
            ],
            [
                'category' => 'Layanan Surat',
                'questions' => [
                    'Bagaimana cara mengajukan surat keterangan?',
                    'Apa saja jenis surat yang tersedia?',
                    'Berapa lama proses pengajuan surat?',
                ]
            ],
            [
                'category' => 'Aduan Masyarakat',
                'questions' => [
                    'Bagaimana cara membuat aduan baru?',
                    'Bagaimana cara melampirkan foto di aduan?',
                    'Bagaimana cara melihat status aduan saya?',
                ]
            ],
            [
                'category' => 'Program Bantuan',
                'questions' => [
                    'Bagaimana cara melihat program bantuan?',
                    'Bagaimana cara mendaftar program bantuan?',
                    'Siapa yang berhak menerima bantuan?',
                ]
            ],
            [
                'category' => 'Umum',
                'questions' => [
                    'Bagaimana cara login ke aplikasi?',
                    'Bagaimana cara reset password?',
                    'Siapa yang bisa saya hubungi jika ada masalah?',
                ]
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }

    /**
     * Check chatbot status and configuration
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'configured' => $this->geminiService->isConfigured(),
                'model' => $this->geminiService->getModel(),
            ]
        ]);
    }

    /**
     * Normalize message for caching
     */
    private function normalizeMessage(string $message): string
    {
        $normalized = strtolower($message);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        return trim($normalized);
    }

    /**
     * Get system context for the chatbot
     */
    private function getSystemContext(): string
    {
        return "Anda adalah asisten AI yang membantu pengguna menggunakan aplikasi Sistem Informasi Desa Digital. 
Jawab dengan bahasa Indonesia yang ramah, jelas, dan mudah dipahami.

## Fitur Aplikasi:

### 1. Data Warga (Menu: Data Warga)
- **Warga**: Input NIK, nama, tempat/tanggal lahir, jenis kelamin, alamat, agama, status perkawinan, pekerjaan
- **Keluarga**: Input nomor KK, kepala keluarga, anggota keluarga
- **Rumah**: Input nomor rumah, RT/RW, jenis rumah, status kepemilikan, foto rumah
- Cara input: Klik menu > submenu > tombol 'Tambah' > isi form > simpan

### 2. Layanan Surat (Menu: Layanan Surat)
- **Jenis Surat**: Surat keterangan domisili, surat keterangan usaha, surat pengantar, dll
- **Cara pengajuan**: Menu Pengajuan Surat > Tambah > pilih jenis surat > isi data > upload berkas pendukung > kirim
- **Status**: Menunggu, Diproses, Disetujui, Ditolak
- Proses: Diajukan > Verifikasi RT > Disetujui Admin Desa > Cetak

### 3. Aduan Masyarakat (Menu: Aduan Masyarakat)
- **Kategori**: Infrastruktur, Kebersihan, Keamanan, Sosial, dll
- **Cara input**: Menu Aduan > Tambah > pilih kategori > tulis deskripsi > lampirkan foto (opsional) > kirim
- **Status**: Menunggu, Diproses, Selesai

### 4. Program Bantuan (Menu: Program Bantuan)
- Melihat daftar program bantuan yang tersedia
- Melihat riwayat penerimaan bantuan
- Kriteria penerima ditentukan admin desa

### 5. Bank Sampah (Menu: Bank Sampah)
- Melihat data bank sampah
- Pencatatan setoran sampah

### 6. Layanan Darurat (Menu: Layanan Darurat)
- Daftar kontak darurat: RT, RW, Puskesmas, Polsek, Damkar, PLN, PDAM
- Informasi kategori layanan darurat

### 7. Berita & Pengumuman (Menu: Berita)
- Informasi dan pengumuman dari desa
- Berita terbaru

## Panduan Umum:
- Login: Masukkan username/email dan password
- Reset password: Hubungi admin atau klik 'Lupa Password'
- Navigasi: Gunakan sidebar menu di sebelah kiri
- Form input: Field dengan tanda * wajib diisi
- Upload file: Klik tombol upload, pilih file, maksimal ukuran sesuai ketentuan

## Penting:
- Jika tidak tahu jawabannya, katakan dengan jujur dan sarankan untuk menghubungi admin
- Jangan memberikan informasi yang tidak akurat
- Fokus pada bantuan penggunaan aplikasi";
    }

    /**
     * Get fallback response when API fails
     */
    private function getFallbackResponse(string $userMessage, string $error): string
    {
        $lowercaseMessage = strtolower($userMessage);

        if (str_contains($lowercaseMessage, 'surat')) {
            return 'Untuk layanan surat, silakan:\n1. Buka menu "Layanan Surat"\n2. Klik "Pengajuan Surat"\n3. Pilih jenis surat yang diinginkan\n4. Isi form dan lampirkan berkas\n5. Klik Kirim\n\nJika membutuhkan bantuan lebih lanjut, silakan hubungi admin desa.';
        }

        if (str_contains($lowercaseMessage, 'aduan') || str_contains($lowercaseMessage, 'lapor')) {
            return 'Untuk membuat aduan:\n1. Buka menu "Aduan Masyarakat"\n2. Klik "Tambah Aduan"\n3. Pilih kategori aduan\n4. Tulis deskripsi masalah\n5. Lampirkan foto jika perlu\n6. Klik Kirim\n\nJika membutuhkan bantuan lebih lanjut, silakan hubungi admin desa.';
        }

        if (str_contains($lowercaseMessage, 'warga') || str_contains($lowercaseMessage, 'penduduk')) {
            return 'Untuk mengelola data warga:\n1. Buka menu "Data Warga"\n2. Pilih submenu (Warga/Keluarga/Rumah)\n3. Klik "Tambah" untuk input baru\n4. Isi semua field yang wajib (*)\n5. Klik Simpan\n\nJika membutuhkan bantuan lebih lanjut, silakan hubungi admin desa.';
        }

        if (str_contains($lowercaseMessage, 'login') || str_contains($lowercaseMessage, 'masuk')) {
            return 'Untuk login:\n1. Masukkan username atau email\n2. Masukkan password\n3. Klik tombol Login\n\nJika lupa password, silakan hubungi admin untuk reset password.';
        }

        return 'Maaf, terjadi gangguan pada sistem. Silakan coba lagi dalam beberapa saat atau hubungi admin untuk bantuan lebih lanjut.';
    }
}
