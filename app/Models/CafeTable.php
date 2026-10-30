<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CafeTable extends Model
{
    use HasFactory;

    protected $table = 'cafe_tables';

    protected $fillable = ['outlet_id', 'name', 'capacity', 'status'];

    protected static function booted(): void
    {
        static::creating(function (self $table): void {
            $table->qr_token ??= Str::random(48);
        });
    }

    public function sessions()
    {
        return $this->hasMany(TableSession::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
