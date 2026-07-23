<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionController extends Controller
{
    private const SORTABLE = ['name', 'slug', 'group', 'roles_count', 'created_at'];

    public function index(Request $request): View
    {
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'group';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage   = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 10;

        $search = trim((string) $request->query('search', ''));
        $group  = $request->query('group');
        $usage  = $request->query('usage'); // used | unused

        $permissions = Permission::query()
            ->withCount('roles')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($group, fn ($q) => $q->where('group', $group))
            ->when($usage === 'used', fn ($q) => $q->has('roles'))
            ->when($usage === 'unused', fn ($q) => $q->doesntHave('roles'))
            ->orderBy($sort, $direction)
            ->when($sort !== 'name', fn ($q) => $q->orderBy('name'))
            ->paginate($perPage)
            ->withQueryString();

        $groups = Permission::query()->distinct()->orderBy('group')->pluck('group');

        $stats = [
            'total'  => Permission::count(),
            'groups' => $groups->count(),
            'unused' => Permission::doesntHave('roles')->count(),
        ];

        return view('permissions.index', compact('permissions', 'groups', 'stats', 'sort', 'direction', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePermission($request);

        Permission::create([
            'name'        => $data['name'],
            'slug'        => $data['slug'] ?: Str::slug($data['name'], '.'),
            'group'       => $data['group'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil ditambahkan.');
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $this->validatePermission($request, $permission);

        $permission->update([
            'name'        => $data['name'],
            'slug'        => $data['slug'] ?: Str::slug($data['name'], '.'),
            'group'       => $data['group'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dihapus.');
    }

    private function validatePermission(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9._-]+$/i', Rule::unique('permissions', 'slug')->ignore($permission?->id)],
            'group'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
