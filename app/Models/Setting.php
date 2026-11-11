<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget("setting.{$setting->key}"));
        static::deleted(fn (Setting $setting) => Cache::forget("setting.{$setting->key}"));
    }

    /**
     * Ambil nilai setting (di-cache), contoh: Setting::get('site_name', 'TopUp Store').
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("setting.{$key}", fn () => static::where('key', $key)->value('value')) ?? $default;
    }
}
