<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'outlet_id',
        'invoice_number',
        'total_cost',
        'status',
        'notes'
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function batches()
    {
        return $this->hasMany(MaterialBatch::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Scope: batasi pembelian sesuai outlet user yang login.
     */
    public function scopeVisibleToCurrentOutlet($query)
    {
        return \App\Support\OutletScope::apply($query);
    }
}
