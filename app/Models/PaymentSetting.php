<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $table = 'paymensettings';

    protected $fillable = [
        'key',
        'value',
        'label',
    ];

    public function scopeKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeKeys($query, array $keys)
    {
        return $query->whereIn('key', $keys);
    }

    public function getStoredValueAttribute(): mixed
    {
        return $this->value;
    }

    public function getIsQrisCodeAttribute(): bool
    {
        return $this->key === 'qris_code';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->key($key)->first();

        return $setting?->value ?? $default;
    }

    public static function set(string $key, mixed $value, string $label = ''): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'label' => $label]
        );
    }

    public static function qrisSettings(): array
    {
        $settings = static::query()
            ->keys(['qris_code', 'qris_name', 'qris_nominal', 'qris_catatan'])
            ->get()
            ->keyBy('key');

        return [
            'qrisCode' => $settings->get('qris_code')?->value,
            'qrisName' => $settings->get('qris_name')?->value ?? '',
            'qrisNominal' => (int) ($settings->get('qris_nominal')?->value ?? 0),
            'qrisCatatan' => $settings->get('qris_catatan')?->value ?? '',
        ];
    }
}
