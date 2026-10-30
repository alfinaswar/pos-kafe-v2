<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'outlet_id',
        'idempotency_key',
        'customer_id',
        'shift_id',
        'total_price',
        'payment_amount',
        'change_amount',
        'payment_method'
        ,'status'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer() { return $this->belongsTo(Customer::class); }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Scope: batasi transaksi sesuai outlet user yang login.
     * Owner/Admin (atau tanpa outlet_id) melihat semua outlet.
     */
    public function scopeVisibleToCurrentOutlet($query)
    {
        return \App\Support\OutletScope::apply($query);
    }
}
