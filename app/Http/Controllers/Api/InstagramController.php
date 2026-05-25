<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstagramController extends Controller
{
    public function post(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'caption' => ['required', 'string', 'max:2200'],
            'media_url' => ['required', 'url'],
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Instagram post request received',
            'data' => [
                'caption' => $validated['caption'],
                'media_url' => $validated['media_url'],
            ],
        ]);
    }
}
