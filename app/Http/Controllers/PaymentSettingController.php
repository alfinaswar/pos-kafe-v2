<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payment-settings');
    }

    public function edit(): View
    {
        $settings = PaymentSetting::whereIn('key', ['doku_client_id', 'doku_secret_key', 'doku_mode', 'doku_enabled'])
            ->pluck('value', 'key');

        return view('settings.payment', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'doku_client_id' => ['required', 'string', 'max:128'],
            'doku_secret_key' => ['nullable', 'string', 'max:255'],
            'doku_mode' => ['required', 'in:sandbox,production'],
        ]);

        foreach (['doku_client_id', 'doku_mode'] as $key) {
            PaymentSetting::updateOrCreate(['key' => $key], ['value' => $data[$key], 'is_secret' => false]);
        }

        if ($request->filled('doku_secret_key')) {
            PaymentSetting::updateOrCreate(['key' => 'doku_secret_key'], ['value' => $data['doku_secret_key'], 'is_secret' => true]);
        }

        PaymentSetting::updateOrCreate(['key' => 'doku_enabled'], ['value' => $request->boolean('doku_enabled') ? '1' : '0', 'is_secret' => false]);

        return redirect()->route('settings.payment')->with('success', 'Pengaturan DOKU tersimpan.');
    }
}
