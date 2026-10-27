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
}
