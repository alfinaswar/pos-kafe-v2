<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TableSession extends Model
{
    use HasFactory;

    protected $fillable = ['cafe_table_id', 'status', 'closed_at'];

    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            $session->session_token ??= Str::random(48);
        });
    }

    public function table()
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public function orders()
    {
        return $this->hasMany(CustomerOrder::class);
    }
}
