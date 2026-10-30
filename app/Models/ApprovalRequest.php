<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $fillable = ['outlet_id', 'requested_by', 'approved_by', 'type', 'reference_type', 'reference_id', 'reason', 'status', 'decision_note', 'decided_at'];
    protected $casts = ['decided_at' => 'datetime'];
}
