<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CaptionGeneratorService;

class TestGenerateCaption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'caption:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test generate caption via Groq';

    /**
     * Execute the console command.
     */
    public function handle(CaptionGeneratorService $service): void
    {
        $this->info('⏳ Generate caption...');

        try {
            $results = $service->generate([
                'platform' => 'instagram',
                'topic'    => 'promosi kopi arabika di Jember',
                'tone'     => 'santai',
                'audience' => 'mahasiswa',
                'count'    => 2,
            ]);

            $this->info('✅ Berhasil! Hasil caption:');
            $this->newLine();

            foreach ($results as $i => $caption) {
                $this->line('Caption ' . ($i + 1) . ':');
                $this->line($caption['content']);
                $this->line('Hashtag: ' . implode(' ', $caption['hashtags']));
                $this->newLine();
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
