<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CustomerComplaint extends Model {protected $fillable=['customer_id','message','status','response'];}
