<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    private const SORTABLE = ['name', 'slug', 'permissions_count', 'is_locked', 'created_at'];

    public function index(Request $request): View
    {
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage   = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 10;

        $search = trim((string) $request->query('search', ''));
        $type   = $request->query('type'); // system | custom

        $roles = Role::query()
            ->withCount('permissions')
            ->with('permissions:id')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($type === 'system', fn ($q) => $q->where('is_locked', true))
            ->when($type === 'custom', fn ($q) => $q->where('is_locked', false))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');

        // permission ids per role (halaman ini saja) untuk mengisi modal edit
        $rolePermissions = $roles->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('id')->all()]);

        $stats = [
            'total'       => Role::count(),
            'locked'      => Role::where('is_locked', true)->count(),
            'permissions' => Permission::count(),
        ];

        return view('roles.index', compact('roles', 'permissions', 'rolePermissions', 'stats', 'sort', 'direction', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        $role = Role::create([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validateRole($request, $role);

        $role->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_locked) {
            return redirect()->route('roles.index')->with('error', 'Role sistem tidak dapat dihapus.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
    }
}
