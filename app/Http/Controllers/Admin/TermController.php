<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class TermController extends Controller
{
    public function index(Request $request)
    {
        $query = Term::query();

        // SEARCH
        if ($request->search) {
            $query->where('term_name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('backend/terms/Index', [
            'terms' => $query->latest()->paginate(7)->withQueryString(),
            'filters' => [
                'search' => $request->search ?? '',
            ]
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('backend/terms/Create');
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
        return Inertia::render('backend/terms/Edit', [
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
