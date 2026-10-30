<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductBundleItem extends Model { protected $fillable=['bundle_product_id','product_id','quantity']; public function product(){return $this->belongsTo(Product::class);} }
