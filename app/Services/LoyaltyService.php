<?php
namespace App\Services;
use App\Models\Sale;
class LoyaltyService
{
    public function award(Sale $sale): void
    {
        if (!$sale->customer_id || ($sale->status && $sale->status !== 'completed')) return;
        $customer = $sale->customer()->lockForUpdate()->first();
        $points = (int) floor((float) $sale->total_price / 1000);
        $customer->increment('loyalty_points', $points);
        $customer->refresh();
        $level = $customer->loyalty_points >= 500 ? 'platinum' : ($customer->loyalty_points >= 200 ? 'gold' : ($customer->loyalty_points >= 50 ? 'silver' : 'regular'));
        $customer->update(['member_level' => $level]);
    }
}
