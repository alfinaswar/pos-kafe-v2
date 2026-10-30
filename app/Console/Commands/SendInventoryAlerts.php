<?php

namespace App\Console\Commands;

use App\Mail\InventoryAlertMail;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInventoryAlerts extends Command
{
    protected $signature = 'inventory:alert';
    protected $description = 'Send critical stock and expiry alerts by email';

    public function handle(): int
    {
        $critical = Material::whereColumn('stock', '<=', 'reorder_point')->get()->map(fn ($m) => ['name' => $m->name, 'stock' => $m->stock, 'unit' => $m->recipe_unit])->all();
        $expiring = MaterialBatch::with('material')->whereNotNull('expired_date')->whereBetween('expired_date', [today(), today()->addDays(30)])->get()->map(fn ($b) => ['name' => $b->material->name, 'date' => $b->expired_date->format('d/m/Y')])->all();
        if (!$critical && !$expiring) return self::SUCCESS;
        $recipients = User::whereIn('status', ['active', 'leave'])->whereNotNull('email')->get()->filter(fn ($u) => $u->hasAnyRole(['Owner', 'Manager']))->pluck('email');
        foreach ($recipients as $email) Mail::to($email)->send(new InventoryAlertMail($critical, $expiring));
        return self::SUCCESS;
    }
}
