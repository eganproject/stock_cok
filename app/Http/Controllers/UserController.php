<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    private const SORTABLE = ['name', 'email', 'role', 'status', 'joined_at', 'created_at'];

    public function index(Request $request): View
    {
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage   = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 10;

        $search      = trim((string) $request->query('search', ''));
        $roleFilter  = $request->query('role');
        $statusFilter = $request->query('status');
        $joinedFrom  = $request->query('joined_from');
        $joinedTo    = $request->query('joined_to');

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(in_array($roleFilter, ['admin', 'manager', 'staff'], true), fn ($q) => $q->where('role', $roleFilter))
            ->when(in_array($statusFilter, ['active', 'inactive'], true), fn ($q) => $q->where('status', $statusFilter))
            ->when($joinedFrom, fn ($q) => $q->whereDate('joined_at', '>=', $joinedFrom))
            ->when($joinedTo, fn ($q) => $q->whereDate('joined_at', '<=', $joinedTo))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Statistik global (tidak terpengaruh filter)
        $stats = [
            'total'    => User::count(),
            'active'   => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
            'admins'   => User::where('role', 'admin')->count(),
        ];

        return view('users.index', compact('users', 'stats', 'sort', 'direction', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'      => ['required', Rule::in(['admin', 'manager', 'staff'])],
            'phone'     => ['nullable', 'string', 'max:30'],
            'status'    => ['required', Rule::in(['active', 'inactive'])],
            'joined_at' => ['nullable', 'date'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => ['required', Rule::in(['admin', 'manager', 'staff'])],
            'phone'     => ['nullable', 'string', 'max:30'],
            'status'    => ['required', Rule::in(['active', 'inactive'])],
            'joined_at' => ['nullable', 'date'],
            'password'  => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
