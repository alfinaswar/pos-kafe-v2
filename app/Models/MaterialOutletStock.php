<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaterialOutletStock extends Model { protected $fillable=['outlet_id','material_id','stock']; protected $casts=['stock'=>'decimal:2']; }
