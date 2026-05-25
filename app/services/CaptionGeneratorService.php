<?php

namespace App\Services;

class CaptionGeneratorService
{
    public function __construct(private GroqService $groq) {}

    public function generate(array $data): array
    {
        $prompt = $this->buildPrompt($data);
        $raw    = $this->groq->generateText($prompt);

        return $this->parseResponse($raw);
    }

    private function buildPrompt(array $data): string
    {
        $platform = $data['platform'];
        $topic    = $data['topic'];
        $tone     = $data['tone'];
        $audience = $data['audience'];
        $count    = $data['count'] ?? 3;
        $brief    = trim((string) ($data['prompt'] ?? $topic));

        $limits = [
            'instagram' => '150 karakter',
            'twitter'   => '280 karakter',
            'tiktok'    => '100 karakter',
            'facebook'  => '200 karakter',
        ];

        $limit = $limits[$platform] ?? '150 karakter';

        return <<<PROMPT
        Kamu adalah copywriter media sosial profesional Indonesia.

        Tugas: buat {$count} variasi caption {$platform} yang siap dipakai berdasarkan brief pengguna di bawah ini.
        Brief pengguna: "{$brief}"

        Ketentuan:
        - Panjang caption: minimal 3 kalimat, maksimal 5 kalimat (tidak termasuk hashtag)
        - Boleh lebih panjang dari {$limit} asal tetap engaging dan tidak bertele-tele
        - Setiap caption harus punya hook kalimat pertama yang menarik perhatian
        - Tambahkan call-to-action di kalimat terakhir (contoh: "Coba sekarang!", "Share ke temanmu!", "Komen di bawah ya!")
        - Gaya bahasa: {$tone}
        - Target audiens: {$audience}
        - Caption harus terasa seperti tulisan manusia, hangat, natural, dan tidak template
        - Hindari kalimat generik seperti "simak berita terbaru", "apa berita hari ini", atau "berita trending hari ini"
        - Jangan mengulang brief user sebagai kalimat tanya seperti "apa", "kapan", "mengapa", atau "bagaimana"
        - Jika brief menyebut berita/trending/hari ini, buat caption yang terasa editorial dan relevan tanpa mengklaim data real-time
        - Utamakan angle yang spesifik: sorot dampak, rasa penasaran, opini ringan, atau ajakan baca lanjut
        - Sertakan 3-5 hashtag relevan Bahasa Indonesia
        - Gunakan emoji yang sesuai

        Balas HANYA dengan JSON valid ini, tanpa markdown, tanpa teks lain, dan tanpa komentar:
        {
          "captions": [
            {
              "content": "teks caption",
              "hashtags": ["#hashtag1", "#hashtag2"]
            }
          ]
        }
        PROMPT;
    }

    private function parseResponse(string $raw): array
    {
        $clean   = preg_replace('/```json|```/i', '', $raw);
        $decoded = json_decode(trim($clean), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Gagal parsing respons caption dari Groq.');
        }

        return $decoded['captions'] ?? [];
    }
}