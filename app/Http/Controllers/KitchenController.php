<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kitchen-access');
    }

    public function index(): View
    {
        $orders = CustomerOrder::with(['session.table', 'items.product'])
            ->whereHas('session.table', fn ($query) => $query->where('outlet_id', auth()->user()->outlet_id))
            ->whereIn('status', ['accepted', 'preparing', 'ready'])
            ->when(request('destination'), fn ($query, $destination) => $query->where('destination', $destination))
            ->latest()
            ->paginate(30);

        return view('kitchen.index', compact('orders'));
    }

    public function updateStatus(Request $request, CustomerOrder $order): JsonResponse
    {
        abort_unless($order->session->table->outlet_id === auth()->user()->outlet_id, 403);
        $data = $request->validate(['status' => ['required', 'in:accepted,preparing,ready,completed']]);
        $order->update(['status' => $data['status']]);
        return response()->json(['success' => true, 'status' => $order->status]);
    }
}
