<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableRequest extends Model
{
    protected $fillable = ['table_session_id', 'type', 'status'];

    public function session()
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }
}
