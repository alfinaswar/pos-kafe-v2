<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['outlet_id', 'name', 'phone', 'email', 'loyalty_points', 'member_level'];

    protected static function booted(): void
    {
        static::creating(function (self $customer) {
            $customer->loyalty_points ??= 0;
            $customer->member_level ??= 'regular';
        });
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /** Earn poin dari transaksi: 1 poin per N rupiah (N = setting loyalty_rate). */
    public function addLoyaltyPoints(int $points, ?int $saleId = null, ?string $notes = null): void
    {
        if ($points <= 0) return;
        $this->increment('loyalty_points', $points);
        LoyaltyTransaction::create([
            'customer_id' => $this->id,
            'sale_id' => $saleId,
            'type' => 'earn',
            'points' => $points,
            'notes' => $notes,
        ]);
        $this->refresh()->autoPromoteLevel();
    }

    /** Redeem poin jadi potongan harga; mengembalikan nilai rupiah yang dipotong. */
    public function redeemPoints(int $points): int
    {
        if ($points <= 0) return 0;
        abort_if($this->loyalty_points < $points, 422, 'Poin pelanggan tidak mencukupi.');
        $this->decrement('loyalty_points', $points);
        LoyaltyTransaction::create([
            'customer_id' => $this->id,
            'type' => 'redeem',
            'points' => -$points,
            'notes' => 'Penukaran poin di kasir',
        ]);
        return $points * (int) \App\Support\Setting::get('loyalty_redeem_value', 100);
    }

    /** Upgrade level member otomatis berdasarkan total poin. */
    public function autoPromoteLevel(): void
    {
        $levels = [
            'regular' => 0,
            'silver' => (int) \App\Support\Setting::get('level_silver_points', 500),
            'gold' => (int) \App\Support\Setting::get('level_gold_points', 1500),
            'platinum' => (int) \App\Support\Setting::get('level_platinum_points', 5000),
        ];
        $best = 'regular';
        foreach ($levels as $level => $minPoints) {
            if ($this->loyalty_points >= $minPoints) $best = $level;
        }
        if ($this->member_level !== $best) $this->update(['member_level' => $best]);
    }
}
