<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private int $timeout;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-pro');
        $this->timeout = (int) config('services.gemini.timeout', 35);
        $this->maxTokens = (int) config('services.gemini.max_tokens', 3000);
        $this->temperature = (float) config('services.gemini.temperature', 0.7);
    }

    /**
     * Generate content using Gemini API
     */
    public function generateContent(string $prompt, array $options = []): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('GEMINI_API_KEY tidak dikonfigurasi di .env');
        }

        // List of API versions and models to try (in order)
        // Berdasarkan list models yang tersedia, gunakan model terbaru yang support generateContent
        $endpoints = [
            // Model terbaru dan terbaik (Gemini 2.5)
            ['version' => 'v1beta', 'model' => 'gemini-2.5-flash'],
            ['version' => 'v1beta', 'model' => 'gemini-2.5-pro'],
            // Model Gemini 2.0 (lebih stabil)
            ['version' => 'v1beta', 'model' => 'gemini-2.0-flash-001'],
            ['version' => 'v1beta', 'model' => 'gemini-2.0-flash'],
            // Fallback ke model lain yang tersedia
            ['version' => 'v1beta', 'model' => 'gemini-2.5-flash-lite'],
            ['version' => 'v1beta', 'model' => 'gemini-2.0-flash-lite-001'],
            ['version' => 'v1beta', 'model' => 'gemini-2.0-flash-lite'],
            // Coba v1 juga
            ['version' => 'v1', 'model' => 'gemini-2.5-flash'],
            ['version' => 'v1', 'model' => 'gemini-2.0-flash-001'],
        ];

        $lastError = null;

        foreach ($endpoints as $endpoint) {
            try {
                $url = "https://generativelanguage.googleapis.com/{$endpoint['version']}/models/{$endpoint['model']}:generateContent?key={$this->apiKey}";
                
                $response = Http::timeout($this->timeout)
                    ->post($url, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => $options['temperature'] ?? $this->temperature,
                            'maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens,
                        ],
                        'safetySettings' => [
                            [
                                'category' => 'HARM_CATEGORY_HARASSMENT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ]
                        ]
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    // Check if response is blocked by safety settings
                    if (isset($data['promptFeedback']['blockReason'])) {
                        Log::warning('Gemini blocked response', ['reason' => $data['promptFeedback']['blockReason']]);
                        return 'Maaf, saya tidak dapat menjawab pertanyaan tersebut.';
                    }

                    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        Log::error('Gemini API Invalid Response', ['data' => $data]);
                        continue; // Try next endpoint
                    }

                    // Success! Log which endpoint worked
                    Log::info('Gemini API Success', [
                        'version' => $endpoint['version'],
                        'model' => $endpoint['model']
                    ]);

                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }

                // If not successful, log and try next
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                $lastError = $errorMessage;

                Log::warning('Gemini API endpoint failed, trying next', [
                    'version' => $endpoint['version'],
                    'model' => $endpoint['model'],
                    'error' => $errorMessage
                ]);

                // If rate limited, don't try other endpoints
                if ($response->status() === 429) {
                    throw new \Exception('Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.');
                }

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning('Gemini API Connection Error, trying next endpoint', [
                    'version' => $endpoint['version'],
                    'model' => $endpoint['model'],
                    'error' => $e->getMessage()
                ]);
                $lastError = $e->getMessage();
                continue;
            } catch (\Exception $e) {
                // If it's not a connection error, re-throw
                if (str_contains($e->getMessage(), 'Terlalu banyak permintaan')) {
                    throw $e;
                }
                $lastError = $e->getMessage();
                continue;
            }
        }

        // All endpoints failed
        Log::error('Gemini API All Endpoints Failed', [
            'last_error' => $lastError,
            'tried_endpoints' => $endpoints
        ]);

        throw new \Exception('Gagal menghubungi Gemini API: ' . ($lastError ?? 'Semua endpoint gagal'));
    }

    /**
     * Chat with context - for conversational AI
     */
    public function chat(string $userMessage, array $conversationHistory = [], ?string $systemContext = null): string
    {
        $prompt = '';

        // Add system context
        if ($systemContext) {
            $prompt .= $systemContext . "\n\n";
        }

        // Add conversation history
        if (!empty($conversationHistory)) {
            $recentHistory = array_slice($conversationHistory, -6); // Last 6 messages
            foreach ($recentHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Asisten';
                $prompt .= "{$role}: {$msg['content']}\n";
            }
        }

        $prompt .= "User: {$userMessage}\nAsisten:";

        return $this->generateContent($prompt);
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get API key (masked for display)
     */
    public function getMaskedApiKey(): string
    {
        if (!$this->isConfigured()) {
            return 'Not configured';
        }
        return substr($this->apiKey, 0, 10) . '...' . substr($this->apiKey, -4);
    }

    /**
     * Get current model
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * List available models (for debugging)
     */
    public function listModels(): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('GEMINI_API_KEY tidak dikonfigurasi di .env');
        }

        $versions = ['v1beta', 'v1'];
        $allModels = [];

        foreach ($versions as $version) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get("https://generativelanguage.googleapis.com/{$version}/models?key={$this->apiKey}");

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['models'])) {
                        foreach ($data['models'] as $model) {
                            $modelName = $model['name'] ?? 'unknown';
                            // Extract model name from full path (e.g., "models/gemini-1.5-flash-001" -> "gemini-1.5-flash-001")
                            if (str_contains($modelName, '/')) {
                                $modelName = explode('/', $modelName)[1] ?? $modelName;
                            }
                            
                            $allModels[] = [
                                'name' => $modelName,
                                'displayName' => $model['displayName'] ?? $modelName,
                                'version' => $version,
                                'supportedMethods' => $model['supportedGenerationMethods'] ?? [],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to list models for {$version}", ['error' => $e->getMessage()]);
            }
        }

        // Remove duplicates
        $uniqueModels = [];
        $seen = [];
        foreach ($allModels as $model) {
            $key = $model['name'] . '-' . $model['version'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueModels[] = $model;
            }
        }

        return $uniqueModels;
    }
}
