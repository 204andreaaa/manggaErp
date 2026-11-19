<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Company-wide settings an admin can change in the app (master database). */
class AppSetting extends Model
{
    protected $connection = 'master';

    protected $table = 'app_settings';

    protected $fillable = ['key', 'value'];

    public const INVOICE_PPN_RATE = 'invoice_ppn_rate';
    public const INVOICE_PPH_RATE = 'invoice_pph_rate';

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
