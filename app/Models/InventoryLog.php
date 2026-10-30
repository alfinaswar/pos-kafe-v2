<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'batch_id',
        'type',
        'quantity',
        'stock_after',
        'reference'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function batch()
    {
        return $this->belongsTo(MaterialBatch::class, 'batch_id');
    }
}
