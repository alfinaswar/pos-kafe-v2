<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class MaterialBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'purchase_id',
        'batch_number',
        'initial_qty',
        'remaining_qty',
        'purchase_price',
        'expired_date',
    ];

    protected $casts = [
        'initial_qty' => 'decimal:2',
        'remaining_qty' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'expired_date' => 'date',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'batch_id');
    }

    public function isExpired(): bool
    {
        return $this->expired_date && Carbon::parse($this->expired_date)->isPast();
    }

    public function isNearExpiry(int $days = 7): bool
    {
        if (!$this->expired_date) {
            return false;
        }
        $exp = Carbon::parse($this->expired_date);
        return $exp->isFuture() && $exp->diffInDays(now()) <= $days;
    }
}
