<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Warehouse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    private const SORTABLE = ['code', 'name', 'sequence', 'capacity', 'is_active', 'last_synced_at', 'created_at'];

    public function index(Request $request): View
    {
        $sort      = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'code';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPage   = in_array((int) $request->query('per_page'), [10, 25, 50, 100], true) ? (int) $request->query('per_page') : 10;

        $search     = trim((string) $request->query('search', ''));
        $divisionId = $request->query('division');
        $active     = $request->query('active');

        $warehouses = Warehouse::query()
            ->with('division')
            ->withCount('stocks')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->when($active === '1', fn ($q) => $q->where('is_active', true))
            ->when($active === '0', fn ($q) => $q->where('is_active', false))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $divisions = Division::orderBy('name')->get();

        $stats = [
            'total'      => Warehouse::count(),
            'active'     => Warehouse::where('is_active', true)->count(),
            'configured' => Warehouse::whereNotNull('base_url')->whereNotNull('api_token')->count(),
            'divisions'  => $divisions->count(),
        ];

        return view('warehouses.index', compact('warehouses', 'divisions', 'stats', 'sort', 'direction', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateWarehouse($request);

        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        Warehouse::create($data);

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $this->validateWarehouse($request, $warehouse);

        // Token hanya diperbarui bila diisi — dikosongkan berarti "biarkan seperti semula"
        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        $warehouse->update($data);

        return redirect()->route('warehouses.index')->with('success', 'Data gudang berhasil diperbarui.');
    }

    /**
     * Uji koneksi ke gudang dengan memanggil endpoint /api/v1/health.
     * Bersifat read-only (tidak menyentuh data stok) dan dipanggil via AJAX.
     */
    public function testConnection(Warehouse $warehouse): JsonResponse
    {
        if (blank($warehouse->base_url)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Base URL belum diatur untuk gudang ini.',
            ]);
        }

        $start = microtime(true);

        try {
            $response = $warehouse->apiRequest(8)->get('/api/v1/health');
            $latency  = (int) round((microtime(true) - $start) * 1000);
            $body     = $response->json() ?? [];

            if (! $response->successful()) {
                return response()->json([
                    'ok'         => false,
                    'status'     => $response->status(),
                    'latency_ms' => $latency,
                    'message'    => 'Server merespons dengan HTTP ' . $response->status()
                        . ($response->status() === 401 ? ' — token kemungkinan salah.' : '.'),
                ]);
            }

            if (! ($body['success'] ?? false)) {
                return response()->json([
                    'ok'         => false,
                    'status'     => $response->status(),
                    'latency_ms' => $latency,
                    'message'    => 'Terhubung, tetapi respons tidak sesuai format kontrak (field "success" tidak true).',
                ]);
            }

            $remoteCode = $body['warehouse_code'] ?? null;

            return response()->json([
                'ok'             => true,
                'status'         => $response->status(),
                'latency_ms'     => $latency,
                'server_time'    => $body['server_time'] ?? null,
                'warehouse_code' => $remoteCode,
                'code_match'     => $remoteCode !== null ? ($remoteCode === $warehouse->code) : null,
                'message'        => 'Koneksi berhasil.',
            ]);
        } catch (ConnectionException $e) {
            return response()->json([
                'ok'         => false,
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'message'    => 'Tidak dapat terhubung (timeout / host tidak dapat dijangkau / SSL bermasalah).',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Gagal: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->stocks()->exists()) {
            return redirect()->route('warehouses.index')
                ->with('error', 'Gudang masih memiliki data stok. Nonaktifkan gudang alih-alih menghapusnya.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Gudang berhasil dihapus.');
    }

    private function validateWarehouse(Request $request, ?Warehouse $warehouse = null): array
    {
        $validated = $request->validate([
            'division_id' => ['required', 'exists:divisions,id'],
            'code'        => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('warehouses', 'code')->ignore($warehouse?->id)],
            'name'        => ['required', 'string', 'max:255'],
            'sequence'    => ['nullable', 'integer', 'min:0', 'max:65535'],
            'address'     => ['nullable', 'string', 'max:255'],
            'capacity'    => ['nullable', 'integer', 'min:0'],
            'base_url'    => ['nullable', 'url', 'max:255'],
            'auth_type'   => ['required', Rule::in(['bearer', 'apikey', 'basic', 'none'])],
            'api_token'   => ['nullable', 'string', 'max:500'],
            'timezone'    => ['required', 'string', 'max:64'],
            'is_active'   => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode gudang hanya boleh huruf, angka, tanda hubung, dan garis bawah.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sequence']  = (int) $request->input('sequence', 0);

        return $validated;
    }
}
