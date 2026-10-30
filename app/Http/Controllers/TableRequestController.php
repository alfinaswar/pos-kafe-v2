<?php

namespace App\Http\Controllers;

use App\Models\TableRequest;
use App\Models\TableSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableRequestController extends Controller
{
    public function store(Request $request, string $token): JsonResponse
    {
        $data = $request->validate(['type' => ['required', 'in:call_cashier,request_bill']]);
        $session = TableSession::where('session_token', $token)->where('status', 'open')->firstOrFail();
        $tableRequest = TableRequest::create(['table_session_id' => $session->id, 'type' => $data['type'], 'status' => 'pending']);
        return response()->json(['success' => true, 'request_id' => $tableRequest->id], 201);
    }

    public function index(): View
    {
        $requests = TableRequest::with('session.table')->where('status', 'pending')->whereHas('session.table', fn ($query) => $query->where('outlet_id', auth()->user()->outlet_id))->latest()->paginate(30);
        return view('table-requests.index', compact('requests'));
    }

    public function update(Request $request, TableRequest $tableRequest): JsonResponse
    {
        abort_unless($tableRequest->session->table->outlet_id === auth()->user()->outlet_id, 403);
        $data = $request->validate(['status' => ['required', 'in:handled,cancelled']]);
        $tableRequest->update(['status' => $data['status']]);
        return response()->json(['success' => true, 'status' => $tableRequest->status]);
    }
}
