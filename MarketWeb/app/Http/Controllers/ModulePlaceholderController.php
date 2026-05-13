<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ModulePlaceholderController extends Controller
{
    public function index(string $module): View
    {
        return view('modules.placeholder', [
            'moduleTitle' => str($module)->replace('-', ' ')->title()->toString(),
        ]);
    }
}
