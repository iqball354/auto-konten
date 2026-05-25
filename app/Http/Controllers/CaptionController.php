<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateCaptionRequest;
use App\Models\Caption;
use App\Services\CaptionGeneratorService;
use Illuminate\Http\Request;

class CaptionController extends Controller
{
    public function __construct(
        private CaptionGeneratorService $captionService,
    ) {}

    public function index()
    {
        $captions = Caption::where('user_id', auth()->id())
            ->latest()
            ->paginate(9);

        return view('captions.index', compact('captions'));
    }

    public function generate(GenerateCaptionRequest $request)
    {
        try {
            $data    = $request->validated();
            $results = $this->captionService->generate($data);

            if ($request->expectsJson()) {
                $firstResult = $results[0] ?? null;

                if (!$firstResult) {
                    return response()->json([
                        'message' => 'Groq tidak mengembalikan caption.',
                    ], 422);
                }

                return response()->json([
                    'caption' => $firstResult['content'] ?? '',
                    'hashtags' => $firstResult['hashtags'] ?? [],
                ]);
            }

            return back()->with('success', 'Caption berhasil digenerate.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Gagal generate: ' . $e->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal generate: ' . $e->getMessage());
        }
    }

    public function generateAjax(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'prompt' => 'required|string|max:500',
            ]);

            $prompt = $request->input('prompt');

            // Detect platform from prompt
            $platform = 'instagram';
            foreach (['twitter', 'tiktok', 'facebook', 'instagram'] as $plat) {
                if (stripos($prompt, $plat) !== false) {
                    $platform = $plat;
                    break;
                }
            }

            // Generate caption
            $results = $this->captionService->generate([
                'platform' => $platform,
                'topic'    => $prompt,
                'tone'     => 'santai',
                'audience' => 'umum',
                'count'    => 1,
            ]);

            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal generate caption',
                ], 422);
            }

            $full = collect($results)->map(function ($item, $index) {
                $no      = $index + 1;
                $content = $item['content'] ?? '';
                $tags    = implode(' ', $item['hashtags'] ?? []);
                return "{$content}\n\n{$tags}";
            })->implode("\n\n---\n\n");

            return response()->json([
                'success' => true,
                'caption' => $full,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Caption $caption)
    {
        abort_if($caption->user_id !== auth()->id(), 403);
        $caption->delete();

        return back()->with('success', 'Caption berhasil dihapus.');
    }
}