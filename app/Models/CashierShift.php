<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'outlet_id',
        'start_cash',
        'end_cash',
        'total_sales_cash',
        'total_sales_non_cash',
        'expected_cash',
        'actual_cash',
        'variance',
        'status',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'start_cash' => 'decimal:2',
        'end_cash' => 'decimal:2',
        'total_sales_cash' => 'decimal:2',
        'total_sales_non_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }

    public function pettyCashes(): HasMany
    {
        return $this->hasMany(PettyCash::class, 'shift_id');
    }

    public function calculateExpectedCash(): float
    {
        $pettyIn = (float) $this->pettyCashes()->where('type', 'in')->sum('amount');
        $pettyOut = (float) $this->pettyCashes()->where('type', 'out')->sum('amount');
        return (float) $this->start_cash + (float) $this->total_sales_cash + $pettyIn - $pettyOut;
    }
}
