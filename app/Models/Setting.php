<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        if ($setting->is_encrypted && !empty($setting->value)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                return $setting->value;
            }
        }

        return $setting->value ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general', bool $encrypted = false): static
    {
        $storeValue = $value;
        if ($encrypted && !empty($value)) {
            $storeValue = Crypt::encryptString((string) $value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $storeValue,
                'is_encrypted' => $encrypted,
            ]
        );
    }

    public static function getGroup(string $group): array
    {
        $items = static::where('group', $group)->get();
        $result = [];

        foreach ($items as $item) {
            if ($item->is_encrypted && !empty($item->value)) {
                try {
                    $result[$item->key] = Crypt::decryptString($item->value);
                } catch (\Exception $e) {
                    $result[$item->key] = $item->value;
                }
            } else {
                $result[$item->key] = $item->value;
            }
        }

        return $result;
    }

    public static function getAppName(): string
    {
        try {
            return (string) static::get('app_name', 'HIROSHIMA POS');
        } catch (\Throwable) {
            return 'HIROSHIMA POS';
        }
    }

    public static function getAppTagline(): string
    {
        try {
            return (string) static::get('app_tagline', 'Pusat Kendali Operasional F&B Multi-Cabang');
        } catch (\Throwable) {
            return 'Pusat Kendali Operasional F&B Multi-Cabang';
        }
    }

    public static function getCompanyName(): string
    {
        try {
            return (string) static::get('company_name', 'Hiroshima Japanese Street Food');
        } catch (\Throwable) {
            return 'Hiroshima Japanese Street Food';
        }
    }

    public static function getLogoUrl(): ?string
    {
        try {
            $logo = static::get('app_logo');
            if (empty($logo)) {
                return null;
            }
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, 'data:')) {
                return $logo;
            }
            $clean = ltrim($logo, '/');
            if (str_starts_with($clean, 'storage/')) {
                $clean = substr($clean, 8);
            }
            return '/storage/' . $clean;
        } catch (\Throwable) {}
        return null;
    }

    public static function getFaviconUrl(): ?string
    {
        try {
            $favicon = static::get('app_favicon');
            if (empty($favicon)) {
                return null;
            }
            if (str_starts_with($favicon, 'http://') || str_starts_with($favicon, 'https://')) {
                return $favicon;
            }
            $clean = ltrim($favicon, '/');
            if (str_starts_with($clean, 'storage/')) {
                $clean = substr($clean, 8);
            }
            return '/storage/' . $clean;
        } catch (\Throwable) {}
        return null;
    }

    public static function getFooterText(): string
    {
        try {
            return (string) static::get('footer_text', '© ' . date('Y') . ' Hiroshima Japanese Street Food. All rights reserved.');
        } catch (\Throwable) {
            return '© Hiroshima Japanese Street Food';
        }
    }

    public static function getSupportEmail(): string
    {
        try {
            return (string) static::get('support_email', 'hiroshimajapanese@gmail.com');
        } catch (\Throwable) {
            return 'hiroshimajapanese@gmail.com';
        }
    }

    public static function getSupportPhone(): string
    {
        try {
            return (string) static::get('support_phone', '');
        } catch (\Throwable) {
            return '';
        }
    }
}
