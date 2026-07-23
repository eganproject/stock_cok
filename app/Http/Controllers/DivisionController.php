<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DivisionController extends Controller
{
    private const SORTABLE = ['code', 'name', 'warehouses_count', 'products_count', 'created_at'];

    public function index(Request $request): View
    {
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'code';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage   = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 10;

        $search = trim((string) $request->query('search', ''));

        $divisions = Division::query()
            ->withCount(['warehouses', 'products'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total'      => Division::count(),
            'warehouses' => Warehouse::count(),
            'products'   => Product::count(),
        ];

        return view('divisions.index', compact('divisions', 'stats', 'sort', 'direction', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDivision($request);

        Division::create($data);

        return redirect()->route('divisions.index')->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $data = $this->validateDivision($request, $division);

        $division->update($data);

        return redirect()->route('divisions.index')->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        // PENTING: FK divisi memakai cascade delete. Tanpa pengaman ini, menghapus
        // satu divisi akan ikut menghapus seluruh gudang, produk, dan stoknya.
        $warehouses = $division->warehouses()->count();
        $products   = $division->products()->count();

        if ($warehouses > 0 || $products > 0) {
            return redirect()->route('divisions.index')->with(
                'error',
                "Divisi \"{$division->name}\" masih dipakai oleh {$warehouses} gudang dan {$products} produk. "
                . 'Pindahkan atau hapus data tersebut lebih dulu.'
            );
        }

        $division->delete();

        return redirect()->route('divisions.index')->with('success', 'Divisi berhasil dihapus.');
    }

    private function validateDivision(Request $request, ?Division $division = null): array
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:16', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('divisions', 'code')->ignore($division?->id)],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'code.regex' => 'Kode divisi hanya boleh huruf, angka, tanda hubung, dan garis bawah.',
        ]);

        $data['code'] = Str::upper($data['code']);

        return $data;
    }
}
