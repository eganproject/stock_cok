<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="subtitle">Ringkasan inventory seluruh gudang</x-slot>

    <!-- Division filter -->
    <form method="GET" action="{{ route('dashboard') }}" id="dashFilterForm" class="mb-5 flex flex-wrap items-center gap-3">
        <label class="text-sm font-medium text-slate-600">Divisi</label>
        <select name="division" class="filter-select w-full sm:w-64">
            <option value="">Semua divisi</option>
            @foreach ($divisions as $div)
                <option value="{{ $div->id }}" @selected($divisionId == $div->id)>{{ $div->name }}</option>
            @endforeach
        </select>
        @if ($divisionId)
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
        @endif
    </form>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Total Item (SKU)', 'value' => number_format($stats['total_items'], 0, ',', '.'), 'hint' => 'Produk terdaftar',
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>'],
                ['label' => 'Total Stok', 'value' => number_format($stats['total_stock'], 0, ',', '.'), 'hint' => 'Seluruh gudang',
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9M3.75 7.5v9L12 21"/>'],
                ['label' => 'Stok Menipis', 'value' => number_format($stats['low_stock'], 0, ',', '.'), 'hint' => 'Di bawah batas minimum',
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>'],
                ['label' => 'Gudang Aktif', 'value' => $stats['warehouses'], 'hint' => 'Terhubung ke sistem',
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75"/>'],
            ];
        @endphp

        @foreach ($cards as $c)
            <div class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] font-medium text-slate-500">{{ $c['label'] }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">{!! $c['icon'] !!}</svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $c['value'] }}</p>
                <p class="mt-1.5 text-xs text-slate-400">{{ $c['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Chart + warehouses -->
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-6 xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Sebaran Stok per Gudang</h3>
                    <p class="text-sm text-slate-400">Total unit tersimpan di tiap lokasi</p>
                </div>
            </div>
            <div id="stockChart" class="mt-4"></div>
            <p class="mt-3 text-xs text-slate-400">
                Grafik tren pergerakan stok harian akan tersedia setelah riwayat sinkronisasi terkumpul.
            </p>
        </div>

        <!-- Warehouse capacity -->
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="text-base font-semibold text-slate-900">Kapasitas Gudang</h3>
            <p class="text-sm text-slate-400">Tingkat keterisian per lokasi</p>
            <div class="mt-5 space-y-4">
                @forelse ($warehouses as $w)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2 text-sm">
                            <span class="min-w-0 truncate font-medium text-slate-700">{{ $w['name'] }}</span>
                            <span class="shrink-0 text-slate-400">{{ $w['capacity'] !== null ? $w['capacity'] . '%' : '—' }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            @php
                                $pct = $w['capacity'] ?? 0;
                                $bar = $pct >= 85 ? 'bg-rose-500' : ($pct >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                            @endphp
                            <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">{{ number_format($w['stock'], 0, ',', '.') }} unit · {{ $w['division'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada gudang terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Low stock table -->
    <div class="mt-6 rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Perlu Segera Restock</h3>
                <p class="text-sm text-slate-400">Barang dengan stok di bawah batas minimum</p>
            </div>
            <a href="{{ route('inventory.index', ['status' => 'menipis']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">
                Lihat semua
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl min-w-[640px]">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nama Barang</th>
                        <th class="hidden md:table-cell">Gudang</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">Min.</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lowStockItems as $stock)
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $stock->product->sku }}</td>
                            <td class="font-medium text-slate-800">{{ $stock->product->name }}</td>
                            <td class="hidden text-slate-500 md:table-cell">{{ $stock->warehouse->name }}</td>
                            <td class="text-right font-semibold text-rose-600">{{ $stock->qty_formatted }}</td>
                            <td class="text-right text-slate-400">{{ $stock->min_qty }}</td>
                            <td class="text-center">
                                @if ($stock->stock_status === 'habis')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Habis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Menipis
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" title="Semua stok aman" message="Tidak ada barang di bawah batas minimum." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $('.filter-select').each(function () {
                    $(this).select2({ minimumResultsForSearch: Infinity, width: '100%' })
                        .on('change', function () { document.getElementById('dashFilterForm').submit(); });
                });

                const options = {
                    chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                    series: [{ name: 'Total stok', data: @json($chart['values']) }],
                    colors: ['#111111'],
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                    xaxis: {
                        categories: @json($chart['labels']),
                        axisBorder: { show: false }, axisTicks: { show: false },
                        labels: { style: { colors: '#94a3b8' } },
                    },
                    yaxis: { labels: { style: { colors: '#94a3b8' } } },
                    grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                    tooltip: { theme: 'light' },
                };
                new ApexCharts(document.querySelector('#stockChart'), options).render();
            });
        </script>
    @endpush
</x-app-layout>
