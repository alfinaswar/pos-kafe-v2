<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model { protected $fillable = ['outlet_id','name','phone','email','loyalty_points','member_level']; protected static function booted(): void { static::creating(function(self $customer){$customer->loyalty_points ??= 0; $customer->member_level ??= 'regular';}); } public function sales(){return $this->hasMany(Sale::class);} }
