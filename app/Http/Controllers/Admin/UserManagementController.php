<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return Inertia::render('backend/users/Index', [
            'users' => User::query()
                ->latest('id')
                ->get(['id', 'name', 'email'])
                ->map(function (User $user): array {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames()->values(),
                    ];
                }),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return Inertia::render('backend/users/Create', [
            'roleOptions' => Role::query()->pluck('name')->values(),
        ]);
    }

    public function roles(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return Inertia::render('backend/users/Roles', [
            'roles' => Role::query()->pluck('name')->values(),
        ]);
    }

    public function permissions(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return Inertia::render('backend/users/Permissions');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole($validated['role']);

        return redirect('/dashboard/users/create')->with('success', 'User created successfully.');
    }
}
