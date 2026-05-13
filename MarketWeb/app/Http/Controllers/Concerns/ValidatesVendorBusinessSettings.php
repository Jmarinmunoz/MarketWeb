<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ValidatesVendorBusinessSettings
{
    /**
     * @return array{business_name: string, rut: string, address: string, phone: string, email: string}
     */
    protected function validateVendorBusinessRequest(Request $request, string $accountEmail): array
    {
        $accountEmail = trim($accountEmail);
        if ($accountEmail === '') {
            throw ValidationException::withMessages([
                'email' => 'No hay correo de cuenta disponible para el negocio.',
            ]);
        }

        $request->merge([
            'business_name' => trim((string) $request->input('business_name', '')),
            'address' => trim((string) $request->input('address', '')),
        ]);

        $phoneRaw = trim((string) $request->input('phone', ''));
        $phoneDigits = $phoneRaw === '' ? '' : $this->normalizeChileanBusinessPhoneForVendor($phoneRaw);

        if ($phoneRaw !== '' && $phoneDigits === null) {
            throw ValidationException::withMessages([
                'phone' => 'El teléfono debe tener exactamente 8 dígitos (fijo) o 9 dígitos (móvil). Puedes incluir espacios o guiones; solo se guardan los números.',
            ]);
        }

        $request->merge(['phone' => $phoneDigits]);

        $rutRaw = trim((string) $request->input('rut', ''));
        $rutFormatted = $rutRaw === '' ? '' : $this->formatChileanBusinessRutForVendor($rutRaw);

        if ($rutRaw !== '' && $rutFormatted === null) {
            throw ValidationException::withMessages([
                'rut' => 'El RUT debe tener exactamente 8 dígitos más el dígito verificador (0-9 o K). Ejemplo: 771234567 o 77.123.456-7.',
            ]);
        }

        $request->merge(['rut' => $rutFormatted]);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'regex:/^\d{2}\.\d{3}\.\d{3}-[0-9K]$/'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\d{8}|\d{9})$/'],
        ], [
            'business_name.required' => 'El nombre del negocio es obligatorio.',
            'rut.required' => 'El RUT del negocio es obligatorio.',
            'rut.regex' => 'El RUT debe verse como 77.123.456-7 (8 dígitos y verificador).',
            'address.required' => 'La dirección del negocio es obligatoria.',
            'phone.required' => 'El teléfono del negocio es obligatorio.',
            'phone.regex' => 'El teléfono debe tener 8 dígitos (fijo) o 9 dígitos (móvil).',
        ]);

        $validated['email'] = $accountEmail;

        return $validated;
    }

    private function normalizeChileanBusinessPhoneForVendor(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);

        if ($digits === '') {
            return null;
        }

        $len = strlen($digits);

        if ($len !== 8 && $len !== 9) {
            return null;
        }

        return $digits;
    }

    private function formatChileanBusinessRutForVendor(string $input): ?string
    {
        $clean = preg_replace('/[^0-9kK]/', '', $input);
        $clean = strtoupper($clean);

        if (strlen($clean) !== 9) {
            return null;
        }

        $verifier = substr($clean, -1);
        $body = substr($clean, 0, 8);

        if ($body === '' || ! ctype_digit($body)) {
            return null;
        }

        if (! preg_match('/^[0-9K]$/', $verifier)) {
            return null;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($body, 0, 2),
            substr($body, 2, 3),
            substr($body, 5, 3),
            $verifier
        );
    }
}
