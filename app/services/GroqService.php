<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.groq.api_key');
        $this->model   = config('services.groq.model');
        $this->baseUrl = config('services.groq.base_url');
    }

    public function generateText(string $prompt): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->model,
                'max_tokens'  => 4096,
                'temperature' => 0.8,
                'top_p'       => 0.9,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Kamu adalah copywriter media sosial profesional Indonesia. Selalu balas dalam format JSON valid tanpa markdown.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Groq API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content') ?? '';
    }
}