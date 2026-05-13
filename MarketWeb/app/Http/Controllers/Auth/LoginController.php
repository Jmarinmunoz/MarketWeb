<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Limita el login a cuentas activas.
     *
     * @return array<string, mixed>
     */
    protected function credentials(Request $request): array
    {
        return [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'status' => true,
        ];
    }

    /**
     * Muestra un mensaje dedicado cuando la cuenta está inactiva.
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request): void
    {
        $email = (string) $request->input('email');

        $inactiveUserExists = User::query()
            ->where('email', $email)
            ->where('status', false)
            ->exists();

        if ($inactiveUserExists) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está temporalmente inactiva. Por favor, contacta al administrador para habilitar el acceso.',
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
