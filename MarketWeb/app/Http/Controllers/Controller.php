<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function currentStoreId(): int
    {
        $user = auth()->user();
        $id = $user?->store_id;

        if ($user?->role?->name === 'Administrador') {
            $selected = (int) session('admin_selected_store_id', 0);
            if ($selected > 0 && Store::query()->whereKey($selected)->where('status', true)->exists()) {
                $id = $selected;
            }
        }

        abort_if($id === null, 403);

        return (int) $id;
    }
}
