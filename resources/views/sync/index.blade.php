<x-app-layout>
    <x-slot name="title">Status Sinkronisasi</x-slot>
    <x-slot name="subtitle">Pantau penarikan data stok dari API tiap gudang</x-slot>

    @php
        $syncBadge = [
            'success' => ['label' => 'Sukses',  'cls' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
            'failed'  => ['label' => 'Gagal',   'cls' => 'bg-rose-50 text-rose-700',       'dot' => 'bg-rose-500'],
            'running' => ['label' => 'Berjalan','cls' => 'bg-amber-50 text-amber-700',      'dot' => 'bg-amber-500'],
            'never'   => ['label' => 'Belum',   'cls' => 'bg-slate-100 text-slate-500',    'dot' => 'bg-slate-400'],
        ];
    @endphp

    <!-- Stat cards -->
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Gudang Terkonfigurasi', 'value' => $stats['configured'],
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>'],
                ['label' => 'Sync Sukses', 'value' => $stats['success'],
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'],
                ['label' => 'Sync Gagal', 'value' => $stats['failed'],
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>'],
                ['label' => 'Item Tersinkron', 'value' => number_format($stats['items'], 0, ',', '.'),
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>'],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] font-medium text-slate-500">{{ $c['label'] }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">{!! $c['icon'] !!}</svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $c['value'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Status per gudang -->
    <div class="mt-6 rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
            <h3 class="text-base font-semibold text-slate-900">Status per Gudang</h3>
            <p class="text-sm text-slate-400">Sinkronisasi manual bisa dijalankan langsung dari sini</p>
        </div>

        <div class="overflow-x-auto">
            <table class="tbl min-w-[820px]">
                <thead>
                    <tr>
                        <th>Gudang</th>
                        <th>Status</th>
                        <th>Terakhir Sync</th>
                        <th class="text-right">Item</th>
                        <th>Keterangan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses as $w)
                        @php $b = $syncBadge[$w->sync_status] ?? $syncBadge['never']; $log = $lastLogs[$w->id] ?? null; @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-semibold text-white">{{ substr($w->code, 0, 3) }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-800">{{ $w->name }}</p>
                                        <p class="truncate font-mono text-xs text-slate-400">{{ $w->code }} · {{ $w->division->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $b['cls'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $b['dot'] }}"></span> {{ $b['label'] }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-500">
                                @if ($w->last_synced_at)
                                    {{ $w->last_synced_at->translatedFormat('d M Y, H:i') }}
                                    @if ($log && $log->duration_seconds !== null)
                                        <span class="block text-[11px] text-slate-400">{{ $log->records_processed }} baris · {{ $log->duration_seconds }} dtk</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-right font-medium text-slate-700">{{ number_format($w->stocks_count, 0, ',', '.') }}</td>
                            <td class="max-w-[260px]">
                                @if ($w->sync_status === 'failed' && $w->last_error)
                                    <span class="truncate text-xs text-rose-600" title="{{ $w->last_error }}">{{ $w->last_error }}</span>
                                @elseif (! $w->isConfigured())
                                    <a href="{{ route('warehouses.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">Belum dikonfigurasi →</a>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($w->isConfigured() && $w->is_active)
                                    <form method="POST" action="{{ route('sync.run', $w) }}" class="inline" x-data="{ busy: false }" @submit="busy = true">
                                        @csrf
                                        <button type="submit" :disabled="busy"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-900 hover:text-white disabled:cursor-wait disabled:opacity-60">
                                            <svg class="h-4 w-4" :class="busy && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            <span x-text="busy ? 'Menyinkronkan…' : 'Sinkronkan'"></span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" title="Belum ada gudang" message="Tambahkan gudang lebih dulu di menu Gudang." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat sinkronisasi -->
    <div class="mt-6 rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-6">
            <h3 class="text-base font-semibold text-slate-900">Riwayat Sinkronisasi</h3>
            <p class="text-sm text-slate-400">Catatan setiap kali sinkronisasi dijalankan</p>
        </div>

        <div class="overflow-x-auto">
            <table class="tbl min-w-[720px]">
                <thead>
                    <tr>
                        <th>Gudang</th>
                        <th>Mulai</th>
                        <th class="text-right">Durasi</th>
                        <th class="text-right">Baris</th>
                        <th>Status</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php $b = $syncBadge[$log->status] ?? $syncBadge['never']; @endphp
                        <tr>
                            <td class="font-mono text-xs text-slate-500">{{ $log->warehouse->code ?? '—' }}</td>
                            <td class="text-sm text-slate-600">{{ $log->started_at->translatedFormat('d M Y, H:i:s') }}</td>
                            <td class="text-right text-sm text-slate-500">{{ $log->duration_seconds !== null ? $log->duration_seconds . ' dtk' : '—' }}</td>
                            <td class="text-right text-sm text-slate-700">{{ number_format($log->records_processed, 0, ',', '.') }}</td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $b['cls'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $b['dot'] }}"></span> {{ $b['label'] }}
                                </span>
                            </td>
                            <td class="max-w-[260px]">
                                @if ($log->error_message)
                                    <span class="truncate text-xs text-rose-600" title="{{ $log->error_message }}">{{ $log->error_message }}</span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" title="Belum ada riwayat" message="Riwayat muncul setelah sinkronisasi pertama dijalankan." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-footer :paginator="$logs" label="catatan" />
    </div>
</x-app-layout>
