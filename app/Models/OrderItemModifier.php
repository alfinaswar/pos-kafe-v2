<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemModifier extends Model
{
    protected $fillable = ['customer_order_item_id', 'key', 'value'];
}
