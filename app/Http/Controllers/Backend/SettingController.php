<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('backend.settings.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->has('action') && $request->string('action')->toString() === 'clear-cache') {
            Artisan::call('optimize:clear');

            return redirect()->route('admin.settings.index')->with('success', 'Application cache cleared.');
        }

        return redirect()->route('admin.settings.index');
    }
}
