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
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $stringValue = self::prepareValue($value, $type);

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
    }

    /**
     * Get all settings as array.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            $settings = [];
            foreach (self::all() as $setting) {
                $settings[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $settings;
        });
    }

    /**
     * Get settings by group.
     */
    public static function getByGroup(string $group): array
    {
        $settings = [];
        foreach (self::where('group', $group)->get() as $setting) {
            $settings[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $settings;
    }

    /**
     * Cast value to appropriate type.
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Prepare value for storage.
     */
    protected static function prepareValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('settings.all');
        foreach (self::pluck('key') as $key) {
            Cache::forget("setting.{$key}");
        }
    }
}
