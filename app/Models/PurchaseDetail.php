<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'material_id',
        'qty_purchase',
        'unit_price',
        'subtotal',
        'expired_date',
    ];

    protected $casts = [
        'qty_purchase' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'expired_date' => 'date',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
