<x-app-layout>
    <x-slot name="title">Divisi</x-slot>
    <x-slot name="subtitle">Pemisah katalog produk antar unit bisnis</x-slot>

    @php
        $hasErrors = $errors->any();
        $editing   = old('_method') === 'PUT' && old('division_id');
        $hasFilter = request()->filled('search');
    @endphp

    <div x-data="divisionsPage({ openOnLoad: {{ $hasErrors ? 'true' : 'false' }}, editingOnLoad: {{ $editing ? 'true' : 'false' }} })">

        <!-- Info -->
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
            <p>Setiap divisi punya <strong>katalog produk sendiri</strong>. Kode SKU yang sama di divisi berbeda dianggap barang berbeda dan tidak akan tercampur. Gudang wajib berada di bawah salah satu divisi.</p>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @php
                $divCards = [
                    ['label' => 'Total Divisi', 'value' => $stats['total'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>'],
                    ['label' => 'Total Gudang', 'value' => $stats['warehouses'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75"/>'],
                    ['label' => 'Total Produk', 'value' => $stats['products'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>'],
                ];
            @endphp
            @foreach ($divCards as $c)
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
                    <h3 class="text-base font-semibold text-slate-900">Daftar Divisi</h3>
                    <p class="text-sm text-slate-400">Unit bisnis dengan katalog produk terpisah</p>
                </div>
                <button @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Divisi
                </button>
            </div>

            <!-- Filter toolbar -->
            <form method="GET" action="{{ route('divisions.index') }}" id="divFilterForm"
                  class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative sm:w-72">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama divisi..."
                            class="block w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                    @if ($hasFilter)
                        <a href="{{ route('divisions.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-500">
                    Tampil
                    <select name="per_page" onchange="document.getElementById('divFilterForm').submit()"
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
                <table class="tbl min-w-[680px]">
                    <thead>
                        <tr>
                            <x-th-sort column="code" :sort="$sort" :direction="$direction">Divisi</x-th-sort>
                            <th class="hidden md:table-cell">Deskripsi</th>
                            <x-th-sort column="warehouses_count" :sort="$sort" :direction="$direction" align="center" class="text-center">Gudang</x-th-sort>
                            <x-th-sort column="products_count" :sort="$sort" :direction="$direction" align="center" class="text-center">Produk</x-th-sort>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($divisions as $div)
                            @php
                                $divPayload = [
                                    'id'          => $div->id,
                                    'code'        => $div->code,
                                    'name'        => $div->name,
                                    'description' => $div->description,
                                    'in_use'      => $div->warehouses_count > 0 || $div->products_count > 0,
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-[11px] font-semibold text-white">
                                            {{ $div->code }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">{{ $div->name }}</p>
                                            <p class="truncate text-xs text-slate-400 md:hidden">{{ $div->description ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden text-slate-500 md:table-cell">{{ $div->description ?? '—' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('warehouses.index', ['division' => $div->id]) }}"
                                       class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                        {{ $div->warehouses_count }} gudang
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('inventory.index', ['division' => $div->id]) }}"
                                       class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                        {{ $div->products_count }} produk
                                    </a>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" @click="openEdit(@js($divPayload))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        @if ($div->warehouses_count > 0 || $div->products_count > 0)
                                            <span title="Masih dipakai gudang/produk — tidak dapat dihapus"
                                                  class="inline-flex cursor-not-allowed rounded-lg p-2 text-slate-200">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                            </span>
                                        @else
                                            <button type="button" title="Hapus" @click="confirmDelete({{ $div->id }}, @js($div->name))"
                                                class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="5" title="Divisi tidak ditemukan" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$divisions" label="divisi" />
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="modalOpen" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-start justify-center p-4 sm:items-center">
                <div x-show="modalOpen" x-transition class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                    <form method="POST" :action="formAction" x-ref="divForm">
                        @csrf
                        <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                        <input type="hidden" name="division_id" :value="divId">

                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z"/></svg>
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'edit' ? 'Edit Divisi' : 'Tambah Divisi'"></h3>
                                    <p class="text-xs text-slate-400">Unit bisnis dengan katalog terpisah</p>
                                </div>
                            </div>
                            <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kode Divisi</label>
                                <input type="text" name="code" value="{{ old('code') }}" required placeholder="cth: OPS"
                                    class="block w-full rounded-xl border-slate-200 font-mono text-sm uppercase focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-[11px] text-slate-400" x-show="inUse" x-cloak>
                                    Divisi ini sudah dipakai. Mengubah kode aman — relasi data memakai ID, bukan kode.
                                </p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Divisi</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi</label>
                                <input type="text" name="description" value="{{ old('description') }}"
                                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
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
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Hapus Divisi?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Anda akan menghapus <span class="font-semibold text-slate-700" x-text="deleteName"></span>.
                        Divisi yang masih memiliki gudang atau produk tidak dapat dihapus.
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
    </div>

    @push('scripts')
        <script>
            function divisionsPage(config) {
                return {
                    modalOpen: false,
                    deleteOpen: false,
                    mode: 'create',
                    divId: null,
                    inUse: false,
                    formAction: '{{ route('divisions.store') }}',
                    deleteName: '',
                    deleteAction: '',

                    init() {
                        if (config.openOnLoad) {
                            this.mode = config.editingOnLoad ? 'edit' : 'create';
                            this.divId = '{{ old('division_id') }}';
                            this.formAction = this.mode === 'edit'
                                ? '{{ url('divisions') }}/' + this.divId
                                : '{{ route('divisions.store') }}';
                            this.$nextTick(() => { this.modalOpen = true; });
                        }
                    },

                    openCreate() {
                        this.mode = 'create';
                        this.divId = null;
                        this.inUse = false;
                        this.formAction = '{{ route('divisions.store') }}';
                        const f = this.$refs.divForm;
                        f.code.value = '';
                        f.name.value = '';
                        f.description.value = '';
                        this.modalOpen = true;
                    },

                    openEdit(d) {
                        this.mode = 'edit';
                        this.divId = d.id;
                        this.inUse = !!d.in_use;
                        this.formAction = '{{ url('divisions') }}/' + d.id;
                        const f = this.$refs.divForm;
                        f.code.value = d.code ?? '';
                        f.name.value = d.name ?? '';
                        f.description.value = d.description ?? '';
                        this.modalOpen = true;
                    },

                    closeModal() { this.modalOpen = false; },

                    confirmDelete(id, name) {
                        this.deleteName = name;
                        this.deleteAction = '{{ url('divisions') }}/' + id;
                        this.deleteOpen = true;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
