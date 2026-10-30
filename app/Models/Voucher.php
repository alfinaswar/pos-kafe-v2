<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'discount_amount', 'expires_at', 'starts_at', 'active_from',
        'active_to', 'usage_limit', 'used_count', 'active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'starts_at' => 'datetime',
        'active' => 'boolean',
    ];

    /** Validasi voucher aktif saat ini: window tanggal + jam operasional harian + kuota pemakaian. */
    public function isCurrentlyValid(): bool
    {
        if (!$this->active) return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->expires_at && $now->gt($this->expires_at)) return false;
        if ($this->active_from && $now->format('H:i') < $this->active_from) return false;
        if ($this->active_to && $now->format('H:i') > $this->active_to) return false;
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return false;
        return true;
    }
}
