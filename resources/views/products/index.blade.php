<x-app-layout>
    <x-slot name="title">Master Produk</x-slot>
    <x-slot name="subtitle">Katalog produk langsung dari API gudang</x-slot>

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
                    Katalog <strong>langsung dari API</strong> gudang divisi
                    <strong>{{ $division->name }}</strong> — satu baris per SKU unik.
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
                    ['label' => 'Total Produk', 'value' => number_format($summary['products'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>'],
                    ['label' => 'Kategori', 'value' => number_format($summary['categories'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>'],
                    ['label' => 'Gudang Divisi', 'value' => number_format($summary['warehouses'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>'],
                    ['label' => 'Total Stok', 'value' => number_format($summary['stock'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9M3.75 7.5v9L12 21"/>'],
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
            <form method="GET" action="{{ route('products.index') }}" id="catFilterForm"
                  class="border-b border-slate-100 px-4 py-4 sm:px-6">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Divisi</label>
                        <select id="filterDivision" class="filter-select w-full">
                            @foreach ($divisions as $div)
                                <option value="{{ $div->id }}" @selected($division && $division->id === $div->id)>{{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kategori</label>
                        <select name="category" class="filter-select w-full">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="SKU / nama…"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                        @if (request()->hasAny(['category', 'search']))
                            <a href="{{ route('products.index', ['division' => $division?->id]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
                        @endif
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        Tampil
                        <select name="per_page" onchange="document.getElementById('catFilterForm').submit()"
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
                <table class="tbl min-w-[760px]">
                    <thead>
                        <tr>
                            <x-th-sort column="sku" :sort="$sort" :direction="$direction">SKU</x-th-sort>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction">Nama Barang</x-th-sort>
                            <x-th-sort column="category" :sort="$sort" :direction="$direction" class="hidden lg:table-cell">Kategori</x-th-sort>
                            <x-th-sort column="uom" :sort="$sort" :direction="$direction">UOM</x-th-sort>
                            <x-th-sort column="warehouses" :sort="$sort" :direction="$direction" align="right" class="hidden md:table-cell text-right">Gudang</x-th-sort>
                            <x-th-sort column="stock" :sort="$sort" :direction="$direction" align="right" class="text-right">Total Stok</x-th-sort>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="font-mono text-xs text-slate-500">{{ $item['sku'] }}</td>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-400 lg:hidden">{{ $item['category'] ?? '—' }}</p>
                                </td>
                                <td class="hidden text-slate-500 lg:table-cell">{{ $item['category'] ?? '—' }}</td>
                                <td class="text-slate-500">{{ $item['uom'] }}</td>
                                <td class="hidden text-right md:table-cell" title="{{ $item['warehouse_names'] }}">
                                    <span class="font-medium text-slate-700">{{ $item['warehouse_count'] }}</span>
                                </td>
                                <td class="text-right font-semibold text-slate-800">{{ rtrim(rtrim(number_format($item['stock'], 3, ',', '.'), '0'), ',') }}</td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="6" title="Tidak ada produk"
                                message="Belum ada produk dari API untuk filter ini, atau gudang belum dikonfigurasi." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$items" label="produk" />
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('catFilterForm');
                if (! form) return;

                // Ganti divisi = reset semua sub-filter.
                $('#filterDivision').select2({ minimumResultsForSearch: 8, width: '100%' })
                    .on('change', function () {
                        window.location.href = '{{ route('products.index') }}?division=' + this.value;
                    });

                // Filter kategori: submit form (mempertahankan divisi + lainnya).
                $('[name="category"]').select2({ minimumResultsForSearch: 8, width: '100%', allowClear: false })
                    .on('change', function () { form.submit(); });
            });
        </script>
    @endpush
</x-app-layout>
