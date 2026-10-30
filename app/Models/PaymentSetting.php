<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];

    protected $casts = ['is_secret' => 'boolean'];

    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = ($this->is_secret || ($this->attributes['key'] ?? '') === 'doku_secret_key')
            ? Crypt::encryptString($value)
            : $value;
    }

    public function getValueAttribute($value): ?string
    {
        if (!$value || (!$this->is_secret && $this->getRawOriginal('key') !== 'doku_secret_key')) {
            return $value;
        }

        return Crypt::decryptString($value);
    }
}
