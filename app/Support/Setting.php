<?php
namespace App\Support;

use Illuminate\Support\Facades\DB;

class Setting
{
    /**
     * Helper sederhana untuk key-value settings (mis. konfigurasi loyalty).
     */
    public static function get(string $key, $default = null)
    {
        try {
            $value = DB::table('settings')->where('key', $key)->value('value');
        } catch (\Throwable) {
            return $default; // tabel belum dimigrasi
        }

        return $value ?? $default;
    }

    public static function set(string $key, $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value, 'updated_at' => now()]
        );
    }
}
