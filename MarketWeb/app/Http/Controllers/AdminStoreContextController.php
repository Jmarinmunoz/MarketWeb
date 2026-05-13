<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminStoreContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $store = Store::query()
            ->whereKey((int) $validated['store_id'])
            ->where('status', true)
            ->firstOrFail();

        session(['admin_selected_store_id' => $store->id]);

        return back()->with('status', "Viendo datos del local: {$store->name}");
    }
}
