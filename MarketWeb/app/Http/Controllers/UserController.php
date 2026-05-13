<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesVendorBusinessSettings;
use App\Models\BusinessSetting;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ValidatesVendorBusinessSettings;
    private const OWNER_ADMIN_EMAIL = 'jmarinmun@gmail.com';

    public function index(): View
    {
        $this->enforceSingleAdminPolicy();

        // Los vendedores tienen store_id propio (local aparte); el administrador debe ver a todos los usuarios.
        $users = User::with(['role', 'store.businessSetting'])->latest()->get();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->enforceSingleAdminPolicy();

        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->enforceSingleAdminPolicy();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'status' => ['required', 'boolean'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $sellerRoleId = $this->sellerRoleId();
        $storeId = $this->currentStoreId();

        $store = $this->createSellerStore($validated['name']);
        $storeId = $store->id;

        User::create([
            'store_id' => $storeId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $sellerRoleId,
            'status' => (bool) $validated['status'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $this->enforceSingleAdminPolicy();

        $user->load(['role', 'store.businessSetting']);
        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->enforceSingleAdminPolicy();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'status' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $isOwnerUser = $this->isOwnerUser($user);
        $roleId = $isOwnerUser ? $this->adminRoleId() : $this->sellerRoleId();
        $email = $isOwnerUser ? self::OWNER_ADMIN_EMAIL : $validated['email'];
        $status = $isOwnerUser ? true : (bool) $validated['status'];
        $storeId = $user->store_id;

        if (! $isOwnerUser && $storeId === null) {
            $storeId = $this->createSellerStore($validated['name'])->id;
        }

        $user->update([
            'store_id' => $storeId,
            'name' => $validated['name'],
            'email' => $email,
            'role_id' => $roleId,
            'status' => $status,
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        if (! (bool) $validated['status'] && auth()->id() === $user->id) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta fue desactivada.']);
        }

        return redirect()->route('users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function updateVendorBusiness(Request $request, User $user): RedirectResponse
    {
        $this->enforceSingleAdminPolicy();

        abort_unless($user->role?->name === 'Vendedor', 404);
        abort_if($user->store_id === null, 422);

        $validated = $this->validateVendorBusinessRequest(
            $request,
            trim((string) $user->email)
        );

        $setting = BusinessSetting::query()->firstOrCreate(
            ['store_id' => $user->store_id],
            [
                'currency' => 'CLP',
                'business_name' => null,
                'receipt_message' => null,
            ],
        );

        $payload = $validated;
        if ($setting->vendor_business_completed_at === null) {
            $payload['vendor_business_completed_at'] = now();
        }

        $setting->update($payload);

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'Datos del negocio del vendedor actualizados correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->enforceSingleAdminPolicy();

        abort_unless($this->canDeleteUsers(), 403);
        abort_if(auth()->id() === $user->id, 422, 'No puedes eliminar tu propio usuario.');
        abort_if($this->isOwnerUser($user), 422, 'No puedes eliminar el administrador principal.');

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }

    private function canDeleteUsers(): bool
    {
        $currentUserEmail = trim((string) auth()->user()?->email);

        return strcasecmp($currentUserEmail, self::OWNER_ADMIN_EMAIL) === 0;
    }

    private function isOwnerUser(User $user): bool
    {
        return strcasecmp(trim((string) $user->email), self::OWNER_ADMIN_EMAIL) === 0;
    }

    private function sellerRoleId(): int
    {
        return (int) Role::query()->where('name', 'Vendedor')->value('id');
    }

    private function adminRoleId(): int
    {
        return (int) Role::query()->where('name', 'Administrador')->value('id');
    }

    private function createSellerStore(string $userName): Store
    {
        $baseSlug = Str::slug($userName);
        if ($baseSlug === '') {
            $baseSlug = 'local-vendedor';
        }

        $slug = $baseSlug;
        $n = 1;
        while (Store::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $n;
            $n++;
        }

        return Store::query()->create([
            'name' => 'Local ' . $userName,
            'slug' => $slug,
            'status' => true,
        ]);
    }

    private function enforceSingleAdminPolicy(): void
    {
        $adminRoleId = $this->adminRoleId();
        $sellerRoleId = $this->sellerRoleId();

        if ($adminRoleId === 0 || $sellerRoleId === 0) {
            return;
        }

        User::query()
            ->where('role_id', $adminRoleId)
            ->whereRaw('LOWER(email) <> ?', [strtolower(self::OWNER_ADMIN_EMAIL)])
            ->update(['role_id' => $sellerRoleId]);

        User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(self::OWNER_ADMIN_EMAIL)])
            ->update([
                'role_id' => $adminRoleId,
                'status' => true,
            ]);

        $sellerUsersWithoutStore = User::query()
            ->where('role_id', $sellerRoleId)
            ->whereNull('store_id')
            ->get();

        foreach ($sellerUsersWithoutStore as $sellerUser) {
            $sellerUser->update([
                'store_id' => $this->createSellerStore($sellerUser->name)->id,
            ]);
        }
    }
}
