<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke()
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['status' => 'ok', 'checks' => ['database' => 'ok']]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'checks' => ['database' => 'failed']], 503);
        }
    }
}
