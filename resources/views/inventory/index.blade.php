<x-app-layout>
    <x-slot name="title">Inventory</x-slot>
    <x-slot name="subtitle">Data stok barang seluruh gudang</x-slot>

    @php
        $statusMeta = [
            'tersedia' => ['label' => 'Tersedia', 'cls' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
            'menipis'  => ['label' => 'Menipis',  'cls' => 'bg-amber-50 text-amber-700',    'dot' => 'bg-amber-500'],
            'habis'    => ['label' => 'Habis',    'cls' => 'bg-rose-50 text-rose-700',      'dot' => 'bg-rose-500'],
        ];
        $hasFilter = request()->hasAny(['search', 'division', 'warehouse', 'category', 'status', 'date_from', 'date_to']);
    @endphp

    <!-- Sync notice -->
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <p>Data dibaca dari <strong>database lokal</strong> hasil sinkronisasi. Saat ini masih berisi data awal (seed) karena penarikan API gudang belum aktif — begitu sinkronisasi berjalan, halaman ini otomatis menampilkan data terbaru tanpa perubahan tampilan.</p>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @php
            $sumCards = [
                ['label' => 'Baris Stok', 'value' => number_format($summary['items'], 0, ',', '.'),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>'],
                ['label' => 'Total Stok', 'value' => number_format($summary['stock'], 0, ',', '.'),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9M3.75 7.5v9L12 21"/>'],
                ['label' => 'Stok Menipis', 'value' => number_format($summary['low'], 0, ',', '.'),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>'],
                ['label' => 'Stok Habis', 'value' => number_format($summary['out'], 0, ',', '.'),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>'],
            ];
        @endphp
        @foreach ($sumCards as $c)
            <div class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] font-medium text-slate-500">{{ $c['label'] }}</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">{!! $c['icon'] !!}</svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $c['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white">
        <!-- Filter toolbar -->
        <form method="GET" action="{{ route('inventory.index') }}" id="invFilterForm"
              class="border-b border-slate-100 px-4 py-4 sm:px-6">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Cari</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="SKU, nama barang..."
                            class="block w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Divisi</label>
                    <select name="division" class="filter-select w-full">
                        <option value="">Semua divisi</option>
                        @foreach ($divisions as $div)
                            <option value="{{ $div->id }}" @selected(request('division') == $div->id)>{{ $div->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Gudang</label>
                    <select name="warehouse" class="filter-select w-full">
                        <option value="">Semua gudang</option>
                        @foreach ($warehouses as $divName => $group)
                            <optgroup label="{{ $divName }}">
                                @foreach ($group as $w)
                                    <option value="{{ $w->id }}" @selected(request('warehouse') == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kategori</label>
                    <select name="category" class="filter-select w-full">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Status</label>
                    <select name="status" class="filter-select w-full">
                        <option value="">Semua status</option>
                        <option value="tersedia" @selected(request('status')==='tersedia')>Tersedia</option>
                        <option value="menipis" @selected(request('status')==='menipis')>Menipis</option>
                        <option value="habis" @selected(request('status')==='habis')>Habis</option>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Update dari</label>
                    <input type="text" id="dateFrom" name="date_from" value="{{ request('date_from') }}" placeholder="Tanggal awal"
                        class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Update s/d</label>
                    <input type="text" id="dateTo" name="date_to" value="{{ request('date_to') }}" placeholder="Tanggal akhir"
                        class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                    @if ($hasFilter)
                        <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            Reset
                        </a>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-500">
                    Tampil
                    <select name="per_page" onchange="document.getElementById('invFilterForm').submit()"
                        class="rounded-lg border-slate-200 py-1.5 pl-2 pr-7 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    baris
                </label>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="tbl min-w-[820px]">
                <thead>
                    <tr>
                        <x-th-sort column="sku" :sort="$sort" :direction="$direction">SKU</x-th-sort>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction">Nama Barang</x-th-sort>
                        <x-th-sort column="category" :sort="$sort" :direction="$direction" class="hidden lg:table-cell">Kategori</x-th-sort>
                        <x-th-sort column="warehouse" :sort="$sort" :direction="$direction" class="hidden md:table-cell">Gudang</x-th-sort>
                        <x-th-sort column="stock" :sort="$sort" :direction="$direction" align="right" class="text-right">Stok</x-th-sort>
                        <x-th-sort column="status" :sort="$sort" :direction="$direction">Status</x-th-sort>
                        <x-th-sort column="updated" :sort="$sort" :direction="$direction" class="hidden xl:table-cell">Update</x-th-sort>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $stock)
                        @php $s = $statusMeta[$stock->stock_status]; @endphp
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $stock->product->sku }}</td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $stock->product->name }}</p>
                                <p class="text-xs text-slate-400 lg:hidden">{{ $stock->product->category }}</p>
                            </td>
                            <td class="hidden text-slate-500 lg:table-cell">{{ $stock->product->category ?? '—' }}</td>
                            <td class="hidden md:table-cell">
                                <p class="text-slate-600">{{ $stock->warehouse->name }}</p>
                                <p class="text-xs text-slate-400">{{ $stock->warehouse->division->code }}</p>
                            </td>
                            <td class="text-right">
                                <span class="font-semibold {{ $stock->qty <= $stock->min_qty ? 'text-rose-600' : 'text-slate-800' }}">{{ $stock->qty_formatted }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $stock->product->uom }} · min {{ $stock->min_qty }}</span>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $s['cls'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span> {{ $s['label'] }}
                                </span>
                            </td>
                            <td class="hidden text-slate-500 xl:table-cell">
                                {{ $stock->source_updated_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" title="Barang tidak ditemukan" />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-footer :paginator="$items" label="baris stok" />
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('invFilterForm');

                $('.filter-select').each(function () {
                    $(this).select2({ minimumResultsForSearch: 8, width: '100%' })
                        .on('change', function () { form.submit(); });
                });

                ['#dateFrom', '#dateTo'].forEach(function (sel) {
                    flatpickr(sel, {
                        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                        locale: { firstDayOfWeek: 1 },
                        onClose: function (dates, str, inst) {
                            if (str !== inst.input.defaultValue) form.submit();
                        },
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
