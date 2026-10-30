<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Voucher extends Model {protected $fillable=['code','discount_amount','expires_at','usage_limit','used_count','active'];protected $casts=['expires_at'=>'datetime','active'=>'boolean'];}
