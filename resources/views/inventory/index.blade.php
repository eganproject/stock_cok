<x-app-layout>
    <x-slot name="title">Inventory</x-slot>
    <x-slot name="subtitle">Data stok langsung dari API gudang</x-slot>

    @php
        $statusMeta = [
            'tersedia' => ['label' => 'Tersedia', 'cls' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
            'menipis'  => ['label' => 'Menipis',  'cls' => 'bg-amber-50 text-amber-700',    'dot' => 'bg-amber-500'],
            'habis'    => ['label' => 'Habis',    'cls' => 'bg-rose-50 text-rose-700',      'dot' => 'bg-rose-500'],
        ];
    @endphp

    @if ($divisions->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center">
            <p class="text-sm font-medium text-slate-700">Belum ada divisi</p>
            <p class="mt-1 text-xs text-slate-400">Tambahkan divisi & gudang lebih dulu di menu terkait.</p>
        </div>
    @else
        <!-- Info sumber data -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
            <div class="flex items-start gap-3 text-slate-600">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12c0 4.556 4.03 8.25 9 8.25 1.307 0 2.549-.253 3.68-.712M2.25 12c0-4.556 4.03-8.25 9-8.25 4.97 0 9 3.694 9 8.25 0 1.02-.202 1.999-.572 2.9m-1.5 1.5a2.121 2.121 0 0 1-3 0"/></svg>
                <span>
                    Data diambil <strong>langsung dari API</strong> gudang divisi
                    <strong>{{ $division->name }}</strong>.
                    @if ($fetchedAt)
                        <span class="text-slate-400">· diperbarui {{ \Illuminate\Support\Carbon::parse($fetchedAt)->translatedFormat('d M Y, H:i') }}</span>
                    @endif
                </span>
            </div>
            <a href="{{ request()->fullUrlWithQuery(['fresh' => 1, 'page' => 1]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-900 hover:text-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                Refresh dari API
            </a>
        </div>

        {{-- Peringatan bila ada gudang yang gagal/ belum dikonfigurasi --}}
        @if (! empty($errors))
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="mb-1 flex items-center gap-2 font-medium">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    Sebagian gudang tidak dapat diambil datanya
                </p>
                <ul class="ml-6 list-disc space-y-0.5 text-xs">
                    @foreach ($errors as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
                <div class="rounded-xl border border-slate-200 bg-white p-5">
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
                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Divisi</label>
                        <select id="filterDivision" class="filter-select w-full">
                            @foreach ($divisions as $div)
                                <option value="{{ $div->id }}" @selected($division && $division->id === $div->id)>{{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Gudang</label>
                        <select name="warehouse" id="filterWarehouse" class="filter-select w-full">
                            <option value="">Semua gudang divisi ini</option>
                            @foreach ($divisionWarehouses as $w)
                                <option value="{{ $w->id }}" @selected($selectedWarehouse && $selectedWarehouse->id === $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kategori</label>
                        <select name="category" class="filter-select w-full">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Status</label>
                        <select name="status" class="filter-select w-full">
                            <option value="">Semua status</option>
                            <option value="tersedia" @selected(request('status')==='tersedia')>Tersedia</option>
                            <option value="menipis" @selected(request('status')==='menipis')>Menipis</option>
                            <option value="habis" @selected(request('status')==='habis')>Habis</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="SKU / nama…"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                        @if (request()->hasAny(['warehouse', 'category', 'status', 'search']))
                            <a href="{{ route('inventory.index', ['division' => $division?->id]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
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
                        @forelse ($items as $item)
                            @php $s = $statusMeta[$item['status_key']]; @endphp
                            <tr>
                                <td class="font-mono text-xs text-slate-500">{{ $item['sku'] }}</td>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-400 lg:hidden">{{ $item['category'] ?? '—' }}</p>
                                </td>
                                <td class="hidden text-slate-500 lg:table-cell">{{ $item['category'] ?? '—' }}</td>
                                <td class="hidden text-slate-500 md:table-cell">{{ $item['warehouse_name'] }}</td>
                                <td class="text-right">
                                    <span class="font-semibold {{ $item['qty'] <= $item['min_qty'] ? 'text-rose-600' : 'text-slate-800' }}">{{ rtrim(rtrim(number_format($item['qty'], 3, ',', '.'), '0'), ',') }}</span>
                                    <span class="block text-[11px] text-slate-400">{{ $item['uom'] }} · min {{ $item['min_qty'] }}</span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $s['cls'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span> {{ $s['label'] }}
                                    </span>
                                </td>
                                <td class="hidden text-slate-500 xl:table-cell">
                                    {{ $item['updated'] ? \Illuminate\Support\Carbon::parse($item['updated'])->translatedFormat('d M Y, H:i') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="7" title="Tidak ada data"
                                message="Belum ada stok dari API untuk filter ini, atau gudang belum dikonfigurasi." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$items" label="baris stok" />
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('invFilterForm');
                if (! form) return;

                // Ganti divisi = reset semua sub-filter (gudang/kategori/status milik divisi tsb).
                $('#filterDivision').select2({ minimumResultsForSearch: 8, width: '100%' })
                    .on('change', function () {
                        window.location.href = '{{ route('inventory.index') }}?division=' + this.value;
                    });

                // Filter lain: submit form (mempertahankan divisi + lainnya).
                $('#filterWarehouse, [name="category"], [name="status"]').each(function () {
                    $(this).select2({ minimumResultsForSearch: 8, width: '100%', allowClear: false })
                        .on('change', function () { form.submit(); });
                });
            });
        </script>
    @endpush
</x-app-layout>
