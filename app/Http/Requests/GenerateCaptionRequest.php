<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCaptionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'prompt'   => ['nullable', 'string'],
            'platform' => ['nullable', 'string', 'in:instagram,facebook,tiktok,twitter'],
            'topic'    => ['nullable', 'string', 'max:300'],
            'tone'     => ['nullable', 'string', 'max:100'],
            'audience' => ['nullable', 'string', 'max:100'],
            'count'    => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        // If the caller passed only `prompt`, normalize to expected fields
        if (!empty($input['prompt'])) {
            $this->merge([
                'topic'    => $input['topic'] ?? $input['prompt'],
                'platform' => $input['platform'] ?? 'instagram',
                'tone'     => $input['tone'] ?? 'santai',
                'audience' => $input['audience'] ?? 'umum',
                'count'    => $input['count'] ?? 1,
            ]);
        } else {
            // Ensure defaults when fields missing
            $this->merge([
                'platform' => $input['platform'] ?? 'instagram',
                'tone'     => $input['tone'] ?? 'santai',
                'audience' => $input['audience'] ?? 'umum',
                'count'    => $input['count'] ?? 1,
            ]);
        }
    }
}
