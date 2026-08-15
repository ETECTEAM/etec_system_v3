<?php

namespace App\Modules\Terms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TermController extends Controller
{
    public function index()
    {
        return Inertia::render('backend/terms/Term', [
            'terms' => Cache::remember(Term::CACHE_KEY, 3600, fn () => Term::orderBy('id', 'asc')->get()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('backend/terms/TermCreate');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'term_name' => ['required', 'string', 'max:255'],
        ]);

        Term::create($validated);

        return redirect()->route('terms.index');
    }

    public function edit(Term $term): Response
    {
        return Inertia::render('backend/terms/TermEdit', [
            'term' => $term
        ]);
    }

    public function update(Request $request, Term $term): RedirectResponse
    {
        $validated = $request->validate([
            'term_name' => ['required', 'string', 'max:255'],
        ]);

        $term->update($validated);

        return redirect()->route('terms.index');
    }

    public function destroy(Term $term): RedirectResponse
    {
        $term->delete();

        return redirect()->route('terms.index');
    }
}
