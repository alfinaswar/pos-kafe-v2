<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $fillable = ['table_session_id', 'order_number', 'status', 'destination', 'total', 'notes'];

    protected $casts = ['total' => 'decimal:2'];

    public function session()
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }

    public function items()
    {
        return $this->hasMany(CustomerOrderItem::class);
    }
}
