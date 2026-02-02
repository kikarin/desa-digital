<?php

namespace App\Console\Commands;

use App\Services\GeminiService;
use Illuminate\Console\Command;

class TestGemini extends Command
{
    protected $signature = 'gemini:test {--message= : Custom message to test} {--list-models : List available models}';
    protected $description = 'Test koneksi ke Google Gemini API';

    public function handle(): int
    {
        $this->info('');
        $this->info('🤖 Testing Google Gemini API Configuration');
        $this->info('==========================================');
        $this->newLine();

        // Check config
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            $this->error('❌ GEMINI_API_KEY tidak ditemukan di .env');
            $this->warn('');
            $this->warn('Langkah untuk mendapatkan API key:');
            $this->line('  1. Buka https://aistudio.google.com/apikey');
            $this->line('  2. Login dengan akun Google');
            $this->line('  3. Klik "Create API Key"');
            $this->line('  4. Salin API key dan tambahkan ke .env:');
            $this->line('     GEMINI_API_KEY=your-api-key-here');
            return 1;
        }

        try {
            $service = new GeminiService();
            
            // List models if requested
            if ($this->option('list-models')) {
                $this->info('📋 Fetching available models...');
                $this->newLine();
                
                $models = $service->listModels();
                
                if (empty($models)) {
                    $this->error('❌ Tidak ada model yang ditemukan atau API key tidak valid');
                    $this->warn('');
                    $this->warn('Kemungkinan penyebab:');
                    $this->line('  1. API key tidak valid');
                    $this->line('  2. Generative Language API belum diaktifkan');
                    $this->line('  3. Koneksi internet bermasalah');
                    return 1;
                }
                
                $this->info('✅ Found ' . count($models) . ' available model(s):');
                $this->newLine();
                
                // Group by version
                $grouped = [];
                foreach ($models as $model) {
                    $grouped[$model['version']][] = $model;
                }
                
                foreach ($grouped as $version => $versionModels) {
                    $this->info("📦 API Version: {$version}");
                    $this->table(
                        ['Model Name', 'Display Name', 'Supports generateContent'],
                        array_map(function($m) {
                            $supports = in_array('generateContent', $m['supportedMethods']) ? '✅ Yes' : '❌ No';
                            return [$m['name'], $m['displayName'], $supports];
                        }, $versionModels)
                    );
                    $this->newLine();
                }
                
                $this->info('💡 Tip: Gunakan model yang memiliki "✅ Yes" untuk generateContent');
                return 0;
            }
            
            $this->info('📋 Configuration:');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['API Key', $service->getMaskedApiKey()],
                    ['Model', $service->getModel()],
                    ['Timeout', config('services.gemini.timeout', 10) . ' seconds'],
                    ['Max Tokens', config('services.gemini.max_tokens', 500)],
                    ['Temperature', config('services.gemini.temperature', 0.7)],
                ]
            );
            $this->newLine();

            // Test API call
            $this->info('📡 Mengirim test request ke Gemini API...');
            
            $testMessage = $this->option('message') 
                ?? 'Halo! Tolong jawab dengan singkat dalam bahasa Indonesia: Siapa nama kamu dan apa fungsimu?';

            $startTime = microtime(true);
            $response = $service->generateContent($testMessage);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            $this->newLine();
            $this->info('✅ Koneksi berhasil!');
            $this->info("⏱️  Response time: {$responseTime}ms");
            $this->newLine();
            
            $this->info('💬 Response dari Gemini:');
            $this->line('─────────────────────────────────────────');
            $this->line($response);
            $this->line('─────────────────────────────────────────');
            $this->newLine();

            $this->info('🎉 Setup Gemini AI berhasil! Chatbot siap digunakan.');
            
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Error: ' . $e->getMessage());
            $this->newLine();
            $this->warn('🔍 Troubleshooting:');
            $this->line('  1. Pastikan API key sudah benar di .env');
            $this->line('  2. Pastikan koneksi internet aktif');
            $this->line('  3. Pastikan API key masih valid (tidak expired/revoked)');
            $this->line('  4. Cek quota: https://aistudio.google.com/apikey');
            $this->line('  5. Jalankan: php artisan config:clear');
            return 1;
        }
    }
}
