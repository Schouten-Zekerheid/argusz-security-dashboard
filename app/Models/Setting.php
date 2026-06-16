<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting by key, with a default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting !== null ? $setting->value : $default;
    }

    /**
     * Set a setting key to a value.
     */
    public static function set(string $key, mixed $value): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
