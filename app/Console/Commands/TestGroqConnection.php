<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGroqConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'groq:test';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test koneksi ke Groq API';
    /**
     * Execute the console command.
     */
   public function handle(): void
    {
        $apiKey  = config('services.groq.api_key');
        $model   = config('services.groq.model');
        $baseUrl = config('services.groq.base_url');

        if (empty($apiKey)) {
            $this->error('❌ GROQ_API_KEY belum diset di .env!');
            return;
        }

        $this->info('⏳ Menghubungkan ke Groq API...');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$baseUrl}/chat/completions", [
                    'model'      => $model,
                    'max_tokens' => 100,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => 'Balas hanya dengan kalimat: API Groq berhasil terhubung!'
                        ]
                    ],
                ]);

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');
                $this->info('✅ Berhasil terhubung!');
                $this->line('📨 Respons Groq: ' . trim($text));
            } else {
                $this->error('❌ Gagal! Status: ' . $response->status());
                $this->line($response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
