<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'price',
        'image',
        'detail',
        'is_bundle'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_bundle' => 'boolean',
    ];

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function bundleItems() { return $this->hasMany(ProductBundleItem::class, 'bundle_product_id'); }
}
