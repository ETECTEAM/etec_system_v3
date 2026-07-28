<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function set(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,km'],
        ]);

        session(['locale' => $validated['locale']]);

        app()->setLocale($validated['locale']);

        return back();
    }
}
