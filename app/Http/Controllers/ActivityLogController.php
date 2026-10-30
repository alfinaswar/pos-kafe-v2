<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activity-log-list');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $logs = ActivityLog::with('user')->latest();

            if ($request->filled('action')) {
                $logs->where('action', $request->action);
            }

            return DataTables::of($logs)
                ->addIndexColumn()
                ->addColumn('user_name', fn($r) => $r->user->name ?? 'Sistem')
                ->addColumn('action_badge', function ($r) {
                    $badges = [
                        'POS_SALE' => 'badge-success',
                        'OPEN_SHIFT' => 'badge-info',
                        'CLOSE_SHIFT' => 'badge-warning',
                        'PETTY_CASH' => 'badge-secondary',
                        'STOCK_OPNAME' => 'badge-danger',
                        'CREATE_PO' => 'badge-primary',
                        'APPROVE_PO' => 'badge-success',
                        'RECEIVE_GOODS' => 'badge-dark',
                    ];
                    $cls = $badges[$r->action] ?? 'badge-info';
                    return '<span class="badge ' . $cls . '">' . $r->action . '</span>';
                })
                ->addColumn('time', fn($r) => $r->created_at->format('d/m/Y H:i:s'))
                ->rawColumns(['action_badge'])
                ->make(true);
        }

        $actions = ActivityLog::select('action')->distinct()->pluck('action');
        return view('activity_logs.index', compact('actions'));
    }
}
