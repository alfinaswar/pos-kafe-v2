<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeldOrder extends Model
{
    protected $fillable = ['user_id', 'outlet_id', 'items', 'notes', 'status'];
    protected $casts = ['items' => 'array'];
}
