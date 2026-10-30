<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokuPayment extends Model
{
    protected $fillable = ['customer_order_id', 'invoice_number', 'request_id', 'status', 'amount', 'payment_url', 'provider_response'];
    protected $casts = ['amount' => 'decimal:2', 'provider_response' => 'array'];

    public function order()
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }
}
