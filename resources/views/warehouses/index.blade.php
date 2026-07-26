<x-app-layout>
    <x-slot name="title">Gudang</x-slot>
    <x-slot name="subtitle">Master data gudang dan konfigurasi API</x-slot>

    @php
        $hasErrors = $errors->any();
        $editing   = old('_method') === 'PUT' && old('warehouse_id');
        $hasFilter = request()->hasAny(['search', 'division', 'active']);
    @endphp

    <div x-data="warehousesPage({ openOnLoad: {{ $hasErrors ? 'true' : 'false' }}, editingOnLoad: {{ $editing ? 'true' : 'false' }} })">

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @php
                $whCards = [
                    ['label' => 'Total Gudang', 'value' => $stats['total'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75"/>'],
                    ['label' => 'Aktif', 'value' => $stats['active'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'],
                    ['label' => 'API Terkonfigurasi', 'value' => $stats['configured'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>'],
                    ['label' => 'Divisi', 'value' => $stats['divisions'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z"/>'],
                ];
            @endphp
            @foreach ($whCards as $c)
                <div class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-slate-300">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">{!! $c['icon'] !!}</svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-2xl font-semibold tracking-tight text-slate-900">{{ $c['value'] }}</p>
                            <p class="truncate text-[13px] text-slate-500">{{ $c['label'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Table card -->
        <div class="mt-6 rounded-xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Daftar Gudang</h3>
                    <p class="text-sm text-slate-400">Master gudang beserta kredensial API-nya</p>
                </div>
                <button @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Gudang
                </button>
            </div>

            <!-- Filter toolbar -->
            <form method="GET" action="{{ route('warehouses.index') }}" id="whFilterForm"
                  class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative sm:w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama, alamat..."
                            class="block w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <div class="sm:w-52">
                        <select name="division" class="filter-select w-full">
                            <option value="">Semua divisi</option>
                            @foreach ($divisions as $div)
                                <option value="{{ $div->id }}" @selected(request('division') == $div->id)>{{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-40">
                        <select name="active" class="filter-select w-full">
                            <option value="">Semua status</option>
                            <option value="1" @selected(request('active')==='1')>Aktif</option>
                            <option value="0" @selected(request('active')==='0')>Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                    @if ($hasFilter)
                        <a href="{{ route('warehouses.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-500">
                    Tampil
                    <select name="per_page" onchange="document.getElementById('whFilterForm').submit()"
                        class="rounded-lg border-slate-200 py-1.5 pl-2 pr-7 text-xs focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                    baris
                </label>
            </form>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="tbl min-w-[860px]">
                    <thead>
                        <tr>
                            <x-th-sort column="code" :sort="$sort" :direction="$direction">Gudang</x-th-sort>
                            <th class="hidden md:table-cell">Divisi</th>
                            <th class="hidden lg:table-cell">Alamat</th>
                            <x-th-sort column="capacity" :sort="$sort" :direction="$direction" align="right" class="text-right">Kapasitas</x-th-sort>
                            <th class="text-center">Integrasi API</th>
                            <x-th-sort column="is_active" :sort="$sort" :direction="$direction" align="center" class="text-center">Status</x-th-sort>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($warehouses as $w)
                            @php
                                $whPayload = [
                                    'id'          => $w->id,
                                    'division_id' => $w->division_id,
                                    'code'        => $w->code,
                                    'name'        => $w->name,
                                    'address'     => $w->address,
                                    'capacity'    => $w->capacity,
                                    'base_url'    => $w->base_url,
                                    'auth_type'   => $w->auth_type,
                                    'timezone'    => $w->timezone,
                                    'is_active'   => $w->is_active,
                                    'has_token'   => filled($w->api_token),
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-semibold text-white">
                                            {{ substr($w->code, 0, 3) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">{{ $w->name }}</p>
                                            <p class="truncate font-mono text-xs text-slate-400">{{ $w->code }} · {{ $w->stocks_count }} item</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden text-slate-500 md:table-cell">{{ $w->division->name }}</td>
                                <td class="hidden max-w-[240px] truncate text-slate-500 lg:table-cell">{{ $w->address ?? '—' }}</td>
                                <td class="text-right text-slate-600">{{ $w->capacity ? number_format($w->capacity, 0, ',', '.') : '—' }}</td>
                                <td>
                                    <div class="flex flex-col items-center gap-1">
                                        @if ($w->isConfigured())
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Siap
                                            </span>
                                        @elseif (filled($w->base_url))
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Perlu token
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Belum diatur
                                            </span>
                                        @endif
                                        @if (filled($w->base_url))
                                            <span class="block max-w-[190px] truncate font-mono text-[11px] text-slate-400" title="{{ $w->base_url }}">{{ preg_replace('#^https?://#', '', rtrim($w->base_url, '/')) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($w->is_active)
                                        <span class="inline-flex items-center rounded-full bg-slate-900 px-2.5 py-1 text-xs font-medium text-white">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        @if (filled($w->base_url))
                                            <button type="button" title="Test koneksi" @click="testConnection({{ $w->id }}, @js($w->name))"
                                                class="rounded-lg p-2 text-slate-400 transition hover:bg-emerald-50 hover:text-emerald-600">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z"/></svg>
                                            </button>
                                        @endif
                                        <button type="button" title="Edit" @click="openEdit(@js($whPayload))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        <button type="button" title="Hapus" @click="confirmDelete({{ $w->id }}, @js($w->name))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="7" title="Gudang tidak ditemukan" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$warehouses" label="gudang" />
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="modalOpen" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-start justify-center p-4 sm:items-center">
                <div x-show="modalOpen" x-transition class="relative w-full max-w-2xl rounded-xl bg-white shadow-2xl">
                    <form method="POST" :action="formAction" x-ref="whForm">
                        @csrf
                        <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                        <input type="hidden" name="warehouse_id" :value="whId">

                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75"/></svg>
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'edit' ? 'Edit Gudang' : 'Tambah Gudang'"></h3>
                                    <p class="text-xs text-slate-400">Data gudang & konfigurasi penarikan API</p>
                                </div>
                            </div>
                            <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="max-h-[68vh] space-y-5 overflow-y-auto px-6 py-5 scrollbar-thin">
                            <div>
                                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Data Gudang</p>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Divisi</label>
                                        <select name="division_id" class="select2-modal w-full">
                                            @foreach ($divisions as $div)
                                                <option value="{{ $div->id }}" @selected(old('division_id') == $div->id)>{{ $div->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('division_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 text-[11px] text-slate-400">Menentukan katalog produk yang dipakai gudang ini.</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Kode Gudang</label>
                                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="cth: JKT-01"
                                            class="block w-full rounded-xl border-slate-200 font-mono text-sm uppercase focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Gudang</label>
                                        <input type="text" name="name" value="{{ old('name') }}" required
                                            class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Alamat</label>
                                        <input type="text" name="address" value="{{ old('address') }}"
                                            class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Kapasitas (unit)</label>
                                        <input type="number" name="capacity" value="{{ old('capacity') }}" min="0"
                                            class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('capacity') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Zona Waktu</label>
                                        <input type="text" name="timezone" value="{{ old('timezone', 'Asia/Jakarta') }}" required
                                            class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('timezone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Konfigurasi API (opsional)</p>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Base URL</label>
                                        <input type="url" name="base_url" value="{{ old('base_url') }}" placeholder="https://gudang.contoh.com"
                                            class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('base_url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Tipe Autentikasi</label>
                                        <select name="auth_type" class="select2-modal w-full">
                                            @foreach (['bearer' => 'Bearer Token', 'apikey' => 'API Key', 'basic' => 'Basic Auth', 'none' => 'Tanpa Auth'] as $val => $label)
                                                <option value="{{ $val }}" @selected(old('auth_type', 'bearer') === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('auth_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-700">API Token</label>
                                        <input type="password" name="api_token" value="" autocomplete="new-password"
                                            :placeholder="mode === 'edit' && hasToken ? '•••••• (biarkan kosong bila tidak diubah)' : 'Tempel token di sini'"
                                            class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                        @error('api_token') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                        <p class="mt-1 text-[11px] text-slate-400">Disimpan terenkripsi dan tidak pernah ditampilkan kembali.</p>
                                    </div>
                                </div>
                            </div>

                            <label class="flex items-center gap-2.5">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" x-ref="isActive" @checked(old('is_active', true))
                                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/30">
                                <span class="text-sm text-slate-700">Gudang aktif (ikut disinkronkan)</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                            <button type="button" @click="closeModal()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                <span x-text="mode === 'edit' ? 'Simpan Perubahan' : 'Simpan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="deleteOpen" x-transition.opacity @click="deleteOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="deleteOpen" x-transition class="relative w-full max-w-md rounded-xl bg-white p-6 text-center shadow-2xl">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Hapus Gudang?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Anda akan menghapus <span class="font-semibold text-slate-700" x-text="deleteName"></span>.
                        Gudang yang masih memiliki data stok tidak dapat dihapus.
                    </p>
                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="deleteOpen = false" class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                        <form method="POST" :action="deleteAction" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-500">Ya, Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Connection Modal -->
        <div x-show="testOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="testOpen" x-transition.opacity @click="testOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="testOpen" x-transition class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                    <div class="text-center">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Test Koneksi</p>
                        <h3 class="mt-0.5 text-base font-semibold text-slate-900" x-text="testName"></h3>
                    </div>

                    {{-- Sedang menguji --}}
                    <div x-show="testing" class="mt-6 flex flex-col items-center gap-3 py-4">
                        <svg class="h-8 w-8 animate-spin text-slate-400" viewBox="0 0 24 24" fill="none"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/></svg>
                        <p class="text-sm text-slate-500">Menghubungi <span class="font-mono">/api/v1/health</span>…</p>
                    </div>

                    {{-- Hasil --}}
                    <template x-if="!testing && testResult">
                        <div class="mt-5">
                            <div class="flex flex-col items-center gap-3">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full"
                                      :class="testResult.ok ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                                    <template x-if="testResult.ok">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </template>
                                    <template x-if="!testResult.ok">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </template>
                                </span>
                                <p class="text-center text-sm font-medium" :class="testResult.ok ? 'text-slate-800' : 'text-rose-700'" x-text="testResult.message"></p>
                            </div>

                            {{-- Detail --}}
                            <dl class="mt-5 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">
                                <div class="flex items-center justify-between" x-show="testResult.status !== undefined">
                                    <dt class="text-slate-500">HTTP Status</dt>
                                    <dd class="font-mono font-medium text-slate-800" x-text="testResult.status"></dd>
                                </div>
                                <div class="flex items-center justify-between" x-show="testResult.latency_ms !== undefined">
                                    <dt class="text-slate-500">Waktu respons</dt>
                                    <dd class="font-mono font-medium text-slate-800"><span x-text="testResult.latency_ms"></span> ms</dd>
                                </div>
                                <div class="flex items-center justify-between" x-show="testResult.server_time">
                                    <dt class="text-slate-500">Waktu server gudang</dt>
                                    <dd class="font-mono text-xs text-slate-800" x-text="testResult.server_time"></dd>
                                </div>
                                <div class="flex items-center justify-between" x-show="testResult.warehouse_code">
                                    <dt class="text-slate-500">Kode gudang (remote)</dt>
                                    <dd class="font-mono font-medium text-slate-800" x-text="testResult.warehouse_code"></dd>
                                </div>
                            </dl>

                            {{-- Peringatan kode tidak cocok --}}
                            <div x-show="testResult.code_match === false" x-cloak
                                 class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                <span>Kode gudang dari API berbeda dengan kode di sistem ini. Pastikan URL tidak tertukar dengan gudang lain.</span>
                            </div>
                        </div>
                    </template>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="testOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function warehousesPage(config) {
                return {
                    modalOpen: false,
                    deleteOpen: false,
                    mode: 'create',
                    whId: null,
                    hasToken: false,
                    formAction: '{{ route('warehouses.store') }}',
                    deleteName: '',
                    deleteAction: '',
                    testOpen: false,
                    testing: false,
                    testResult: null,
                    testName: '',

                    async testConnection(id, name) {
                        this.testName = name;
                        this.testResult = null;
                        this.testing = true;
                        this.testOpen = true;
                        try {
                            const res = await fetch('{{ url('warehouses') }}/' + id + '/test-connection', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                            });
                            this.testResult = await res.json();
                        } catch (e) {
                            this.testResult = { ok: false, message: 'Permintaan tidak dapat dikirim dari browser.' };
                        } finally {
                            this.testing = false;
                        }
                    },

                    init() {
                        if (config.openOnLoad) {
                            this.mode = config.editingOnLoad ? 'edit' : 'create';
                            this.whId = '{{ old('warehouse_id') }}';
                            this.formAction = this.mode === 'edit'
                                ? '{{ url('warehouses') }}/' + this.whId
                                : '{{ route('warehouses.store') }}';
                            this.$nextTick(() => { this.modalOpen = true; });
                        }
                    },

                    openCreate() {
                        this.mode = 'create';
                        this.whId = null;
                        this.hasToken = false;
                        this.formAction = '{{ route('warehouses.store') }}';
                        const f = this.$refs.whForm;
                        f.code.value = '';
                        f.name.value = '';
                        f.address.value = '';
                        f.capacity.value = '';
                        f.base_url.value = '';
                        f.api_token.value = '';
                        f.timezone.value = 'Asia/Jakarta';
                        this.$refs.isActive.checked = true;
                        $(f.division_id).val($(f.division_id).find('option:first').val()).trigger('change');
                        $(f.auth_type).val('bearer').trigger('change');
                        this.modalOpen = true;
                    },

                    openEdit(w) {
                        this.mode = 'edit';
                        this.whId = w.id;
                        this.hasToken = !!w.has_token;
                        this.formAction = '{{ url('warehouses') }}/' + w.id;
                        const f = this.$refs.whForm;
                        f.code.value = w.code ?? '';
                        f.name.value = w.name ?? '';
                        f.address.value = w.address ?? '';
                        f.capacity.value = w.capacity ?? '';
                        f.base_url.value = w.base_url ?? '';
                        f.api_token.value = '';
                        f.timezone.value = w.timezone ?? 'Asia/Jakarta';
                        this.$refs.isActive.checked = !!w.is_active;
                        $(f.division_id).val(w.division_id).trigger('change');
                        $(f.auth_type).val(w.auth_type || 'bearer').trigger('change');
                        this.modalOpen = true;
                    },

                    closeModal() { this.modalOpen = false; },

                    confirmDelete(id, name) {
                        this.deleteName = name;
                        this.deleteAction = '{{ url('warehouses') }}/' + id;
                        this.deleteOpen = true;
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('whFilterForm');
                $('.filter-select').each(function () {
                    $(this).select2({ minimumResultsForSearch: Infinity, width: '100%' })
                        .on('change', function () { form.submit(); });
                });

                const $shell = $('[x-ref="whForm"]').closest('.rounded-xl');
                $('.select2-modal').select2({ minimumResultsForSearch: Infinity, dropdownParent: $shell, width: '100%' });
            });
        </script>
    @endpush
</x-app-layout>
