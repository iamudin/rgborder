<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $data = Cache::remember("setting_val_{$key}", 3600, function () use ($key) {
                $setting = static::where('key', $key)->first();

                if (! $setting) {
                    return null;
                }

                return [
                    'value' => $setting->value,
                    'type' => $setting->type,
                ];
            });

            if (! is_array($data)) {
                Cache::forget("setting_val_{$key}");
                Cache::forget("setting_{$key}");

                $setting = static::where('key', $key)->first();
                if (! $setting) {
                    return $default;
                }

                $data = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                ];
            }

            return match ($data['type'] ?? 'string') {
                'integer', 'int' => (int) $data['value'],
                'boolean', 'bool' => filter_var($data['value'], FILTER_VALIDATE_BOOLEAN),
                'json', 'array' => json_decode($data['value'], true) ?? $default,
                default => $data['value'] ?? $default,
            };
        } catch (\Throwable $e) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer', 'int' => (int) $setting->value,
                'boolean', 'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json', 'array' => json_decode($setting->value, true) ?? $default,
                default => $setting->value ?? $default,
            };
        }
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): static
    {
        $storedValue = match ($type) {
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            'boolean', 'bool' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("setting_val_{$key}");

        return $setting;
    }
}
