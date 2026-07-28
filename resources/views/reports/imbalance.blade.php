<x-app-layout>
    <x-slot name="title">Laporan Ketimpangan Antar-Gudang</x-slot>
    <x-slot name="subtitle">SKU yang kurang di satu gudang tapi berlebih di gudang lain — peluang transfer</x-slot>

    @php
        $qtyColor = [
            'tersedia' => 'text-slate-800',
            'menipis'  => 'text-amber-600',
            'habis'    => 'text-rose-600',
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
                    Peluang <strong>pemindahan stok</strong> antar gudang di divisi <strong>{{ $division->name }}</strong>.
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
                    ['label' => 'SKU Timpang', 'value' => number_format($summary['items'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/>'],
                    ['label' => 'Total Unit Bisa Dipindah', 'value' => number_format($summary['units'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>'],
                    ['label' => 'Gudang Divisi', 'value' => number_format($summary['warehouses'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>'],
                    ['label' => 'Kategori Terdampak', 'value' => number_format($summary['categories'], 0, ',', '.'),
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>'],
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
            <form method="GET" action="{{ route('reports.imbalance') }}" id="rptFilterForm"
                  class="border-b border-slate-100 px-4 py-4 sm:px-6">
                <input type="hidden" name="division" value="{{ $division?->id }}">
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
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Posisi stok per tanggal</label>
                        <input type="text" id="asOf" name="as_of" value="{{ $asOfRaw }}" placeholder="Stok terkini"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <div class="lg:col-span-6">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="SKU / nama…"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                        @if (request()->hasAny(['category', 'search', 'as_of']))
                            <a href="{{ route('reports.imbalance', ['division' => $division?->id]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
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

            {{-- Legenda warna qty --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 border-b border-slate-100 px-4 py-2.5 text-xs text-slate-500 sm:px-6">
                <span class="font-medium text-slate-400">Warna angka stok:</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-400"></span> Tersedia</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span> <span class="text-amber-600">Menipis</span></span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-500"></span> <span class="text-rose-600">Habis</span></span>
                <span class="text-slate-400">· “Saran” = pindahkan sekian unit dari gudang berlebih ke gudang kurang</span>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="tbl min-w-[880px]">
                    <thead>
                        <tr>
                            <x-th-sort column="sku" :sort="$sort" :direction="$direction">SKU</x-th-sort>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction">Nama Barang</x-th-sort>
                            <x-th-sort column="category" :sort="$sort" :direction="$direction" class="hidden lg:table-cell">Kategori</x-th-sort>
                            @foreach ($divisionWarehouses as $w)
                                <th class="text-right" title="{{ $w->code }}">{{ $w->name }}</th>
                            @endforeach
                            <x-th-sort column="imbalance" :sort="$sort" :direction="$direction" align="right" class="text-right">Ketimpangan</x-th-sort>
                            <th>Saran Transfer</th>
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
                                @foreach ($divisionWarehouses as $w)
                                    @php $cell = $item['per_wh'][$w->code] ?? null; @endphp
                                    <td class="text-right">
                                        @if ($cell)
                                            <span class="font-medium {{ $qtyColor[$cell['status_key']] }}" title="min {{ $fmtQty($cell['min']) }}">{{ $fmtQty($cell['qty']) }}</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-right font-semibold text-slate-900">{{ $fmtQty($item['imbalance']) }}</td>
                                <td>
                                    @if ($item['move'])
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            {{ $item['move']['from'] }}
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                            {{ $item['move']['to'] }}
                                            <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[11px] text-white">{{ $fmtQty($item['move']['qty']) }} {{ $item['uom'] }}</span>
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="5 + $divisionWarehouses->count()" title="Tidak ada ketimpangan"
                                message="Semua stok relatif seimbang antar gudang untuk filter ini (atau divisi ini hanya punya satu gudang)." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$items" label="SKU" />
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('rptFilterForm');
                if (! form) return;

                $('#filterDivision').select2({ minimumResultsForSearch: 8, width: '100%' })
                    .on('change', function () {
                        window.location.href = '{{ route('reports.imbalance') }}?division=' + this.value;
                    });

                $('[name="category"]').select2({ minimumResultsForSearch: 8, width: '100%', allowClear: false })
                    .on('change', function () { form.submit(); });

                flatpickr('#asOf', {
                    dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                    maxDate: 'today', locale: { firstDayOfWeek: 1 },
                    onClose: function (dates, str, inst) { if (str !== inst.input.defaultValue) form.submit(); },
                });
            });
        </script>
    @endpush
</x-app-layout>
