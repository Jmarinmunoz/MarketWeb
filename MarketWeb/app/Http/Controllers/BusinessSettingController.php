<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesVendorBusinessSettings;
use App\Models\BusinessSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BusinessSettingController extends Controller
{
    use ValidatesVendorBusinessSettings;

    public function edit(): View
    {
        $setting = BusinessSetting::query()->firstOrCreate(
            ['store_id' => $this->currentStoreId()],
            [
                'business_name' => null,
                'currency' => 'CLP',
                'receipt_message' => null,
            ]
        );

        $vendorBusinessLocked = auth()->user()->role?->name === 'Vendedor'
            && $setting->vendor_business_completed_at !== null;

        return view('settings.edit', compact('setting', 'vendorBusinessLocked'));
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = BusinessSetting::query()->where('store_id', $this->currentStoreId())->firstOrFail();

        if (auth()->user()->role?->name === 'Vendedor' && $setting->vendor_business_completed_at !== null) {
            abort(403);
        }

        $validated = $this->validateVendorBusinessRequest(
            $request,
            trim((string) auth()->user()->email)
        );

        $payload = $validated;

        if (auth()->user()->role?->name === 'Vendedor') {
            $payload['vendor_business_completed_at'] = now();
        }

        $setting->update($payload);

        return redirect()->route('settings.index')->with('status', 'Configuración registrada correctamente. Ya no podrás modificar estos datos desde aquí; contacta al administrador si necesitas un cambio.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $password = trim((string) $request->input('password', ''));
        $passwordConfirmation = trim((string) $request->input('password_confirmation', ''));

        if ($password === '' && $passwordConfirmation === '') {
            return redirect()
                ->route('settings.index')
                ->with('password_status', 'No se indicó una nueva contraseña.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Debes escribir la nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('settings.index')
            ->with('password_status', 'Contraseña actualizada correctamente.');
    }
}
