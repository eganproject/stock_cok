<x-app-layout>
    <x-slot name="title">Laporan Perlu Restock</x-slot>
    <x-slot name="subtitle">Barang di bawah stok minimum (menipis) atau habis</x-slot>

    @php
        $statusMeta = [
            'menipis' => ['label' => 'Menipis', 'cls' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
            'habis'   => ['label' => 'Habis',   'cls' => 'bg-rose-50 text-rose-700',   'dot' => 'bg-rose-500'],
        ];
        $fmtQty = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ',');
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
                    Barang yang <strong>perlu di-restock</strong> di divisi <strong>{{ $division->name }}</strong>
                    (stok ≤ minimum), langsung dari API.
                    @if ($asOfRaw)
                        <span class="font-medium text-slate-700">· posisi per {{ \Illuminate\Support\Carbon::parse($asOfRaw)->translatedFormat('d M Y') }}</span>
                    @endif
                    @if ($fetchedAt)
                        <span class="text-slate-400">· diperbarui {{ \Illuminate\Support\Carbon::parse($fetchedAt)->translatedFormat('d M Y, H:i') }}</span>
                    @endif
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-600 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                    Export Excel
                </a>
                <a href="{{ request()->fullUrlWithQuery(['fresh' => 1, 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-900 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    Refresh dari API
                </a>
            </div>
        </div>

        {{-- Peringatan gudang gagal --}}
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
                    ['label' => 'Perlu Restock', 'value' => number_format($summary['items'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>'],
                    ['label' => 'Habis', 'value' => number_format($summary['habis'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>'],
                    ['label' => 'Menipis', 'value' => number_format($summary['menipis'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>'],
                    ['label' => 'Total Kekurangan', 'value' => number_format($summary['shortfall'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625Z"/>'],
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
            <form method="GET" action="{{ route('reports.restock') }}" id="rptFilterForm"
                  class="border-b border-slate-100 px-4 py-4 sm:px-6">
                <input type="hidden" name="division" value="{{ $division?->id }}">
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
                        <select name="warehouse" class="filter-select w-full">
                            <option value="">Semua gudang divisi ini</option>
                            @foreach ($divisionWarehouses as $w)
                                <option value="{{ $w->id }}" @selected($selectedWarehouse && $selectedWarehouse->id === $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Ambang</label>
                        <select name="threshold" class="filter-select w-full">
                            <option value="">Menipis & Habis</option>
                            <option value="habis" @selected($threshold==='habis')>Habis saja</option>
                            <option value="menipis" @selected($threshold==='menipis')>Menipis saja</option>
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
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="SKU / nama…"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <div class="lg:col-span-4">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Posisi stok per tanggal</label>
                        <input type="text" id="asOf" name="as_of" value="{{ $asOfRaw }}" placeholder="Stok terkini"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                        @if (request()->hasAny(['warehouse', 'category', 'status', 'search', 'as_of', 'threshold']))
                            <a href="{{ route('reports.restock', ['division' => $division?->id]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
                        @endif
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        Tampil
                        <select name="per_page" onchange="document.getElementById('rptFilterForm').submit()"
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
                <table class="tbl min-w-[860px]">
                    <thead>
                        <tr>
                            <x-th-sort column="sku" :sort="$sort" :direction="$direction">SKU</x-th-sort>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction">Nama Barang</x-th-sort>
                            <x-th-sort column="category" :sort="$sort" :direction="$direction" class="hidden lg:table-cell">Kategori</x-th-sort>
                            <x-th-sort column="warehouse" :sort="$sort" :direction="$direction" class="hidden md:table-cell">Gudang</x-th-sort>
                            <x-th-sort column="stock" :sort="$sort" :direction="$direction" align="right" class="text-right">Stok</x-th-sort>
                            <th class="text-right">Min</th>
                            <x-th-sort column="shortfall" :sort="$sort" :direction="$direction" align="right" class="text-right">Kekurangan</x-th-sort>
                            <x-th-sort column="status" :sort="$sort" :direction="$direction">Status</x-th-sort>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php $s = $statusMeta[$item['status_key']]; @endphp
                            <tr>
                                <td class="font-mono text-xs text-slate-500">{{ $item['sku'] }}</td>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $item['name'] }}</p>
                                    <p class="text-xs text-slate-400 md:hidden">{{ $item['warehouse_name'] }}</p>
                                </td>
                                <td class="hidden text-slate-500 lg:table-cell">{{ $item['category'] ?? '—' }}</td>
                                <td class="hidden text-slate-500 md:table-cell">{{ $item['warehouse_name'] }}</td>
                                <td class="text-right">
                                    <span class="font-semibold {{ $item['status_key'] === 'habis' ? 'text-rose-600' : 'text-amber-600' }}">{{ $fmtQty($item['qty']) }}</span>
                                    <span class="block text-[11px] text-slate-400">{{ $item['uom'] }}</span>
                                </td>
                                <td class="text-right text-slate-500">{{ $fmtQty($item['min_qty']) }}</td>
                                <td class="text-right">
                                    <span class="font-semibold text-slate-900">{{ $fmtQty($item['shortfall']) }}</span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $s['cls'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span> {{ $s['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="8" title="Semua stok aman 🎉"
                                message="Tidak ada barang di bawah minimum untuk filter ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$items" label="barang" />
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('rptFilterForm');
                if (! form) return;

                $('#filterDivision').select2({ minimumResultsForSearch: 8, width: '100%' })
                    .on('change', function () {
                        window.location.href = '{{ route('reports.restock') }}?division=' + this.value;
                    });

                $('[name="warehouse"], [name="threshold"], [name="category"]').each(function () {
                    $(this).select2({ minimumResultsForSearch: 8, width: '100%', allowClear: false })
                        .on('change', function () { form.submit(); });
                });

                flatpickr('#asOf', {
                    dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                    maxDate: 'today', locale: { firstDayOfWeek: 1 },
                    onClose: function (dates, str, inst) { if (str !== inst.input.defaultValue) form.submit(); },
                });
            });
        </script>
    @endpush
</x-app-layout>
