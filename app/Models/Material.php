<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'stock',
        'purchase_unit',
        'recipe_unit',
        'conversion_factor',
        'reorder_point'
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'conversion_factor' => 'decimal:2',
        'reorder_point' => 'decimal:2',
    ];

    // Helper: Cek apakah stok di bawah ROP (kritis)
    public function isLowStock(): bool
    {
        return $this->stock <= $this->reorder_point;
    }

    // Helper konversi stok beli ke stok resep
    public function convertPurchaseToRecipe(float $qtyPurchase): float
    {
        return $qtyPurchase * $this->conversion_factor;
    }

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function batches()
    {
        return $this->hasMany(MaterialBatch::class);
    }

    public function activeBatches()
    {
        return $this->hasMany(MaterialBatch::class)->where('remaining_qty', '>', 0)->orderBy('created_at', 'asc');
    }

    public function outletStocks()
    {
        return $this->hasMany(MaterialOutletStock::class);
    }

    /**
     * Scope: batasi bahan yang relevan dengan outlet user (via material_outlet_stocks).
     */
    public function scopeVisibleToCurrentOutlet($query)
    {
        $outletId = \App\Support\OutletScope::current();

        return $outletId ? $query->whereHas('outletStocks', fn ($s) => $s->where('outlet_id', $outletId)) : $query;
    }
}
