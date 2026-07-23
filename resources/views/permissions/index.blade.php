<x-app-layout>
    <x-slot name="title">Permission</x-slot>
    <x-slot name="subtitle">Kelola daftar hak akses sistem</x-slot>

    @php
        $hasErrors = $errors->any();
        $editing   = old('_method') === 'PUT' && old('permission_id');
        $hasFilter = request()->hasAny(['search', 'group', 'usage']);
    @endphp

    <div x-data="permissionsPage({ openOnLoad: {{ $hasErrors ? 'true' : 'false' }}, editingOnLoad: {{ $editing ? 'true' : 'false' }} })">

        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @php
                $permCards = [
                    ['label' => 'Total Permission', 'value' => $stats['total'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>'],
                    ['label' => 'Grup', 'value' => $stats['groups'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>'],
                    ['label' => 'Belum Dipakai', 'value' => $stats['unused'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>'],
                ];
            @endphp
            @foreach ($permCards as $c)
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
                    <h3 class="text-base font-semibold text-slate-900">Daftar Permission</h3>
                    <p class="text-sm text-slate-400">Hak akses yang dapat ditetapkan ke role</p>
                </div>
                <button @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Permission
                </button>
            </div>

            <!-- Filter toolbar -->
            <form method="GET" action="{{ route('permissions.index') }}" id="permFilterForm"
                  class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative sm:w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari permission..."
                            class="block w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <div class="sm:w-48">
                        <select name="group" class="filter-select w-full">
                            <option value="">Semua grup</option>
                            @foreach ($groups as $g)
                                <option value="{{ $g }}" @selected(request('group') === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-40">
                        <select name="usage" class="filter-select w-full">
                            <option value="">Semua status</option>
                            <option value="used" @selected(request('usage')==='used')>Dipakai role</option>
                            <option value="unused" @selected(request('usage')==='unused')>Belum dipakai</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                    @if ($hasFilter)
                        <a href="{{ route('permissions.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-500">
                    Tampil
                    <select name="per_page" onchange="document.getElementById('permFilterForm').submit()"
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
                <table class="tbl min-w-[720px]">
                    <thead>
                        <tr>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction">Permission</x-th-sort>
                            <x-th-sort column="slug" :sort="$sort" :direction="$direction" class="hidden md:table-cell">Slug</x-th-sort>
                            <x-th-sort column="group" :sort="$sort" :direction="$direction">Grup</x-th-sort>
                            <x-th-sort column="roles_count" :sort="$sort" :direction="$direction" align="center" class="text-center">Dipakai</x-th-sort>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $perm)
                            @php
                                $permPayload = [
                                    'id'          => $perm->id,
                                    'name'        => $perm->name,
                                    'slug'        => $perm->slug,
                                    'group'       => $perm->group,
                                    'description' => $perm->description,
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $perm->name }}</p>
                                    @if ($perm->description)
                                        <p class="text-xs text-slate-400">{{ $perm->description }}</p>
                                    @endif
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-400 md:hidden">{{ $perm->slug }}</p>
                                </td>
                                <td class="hidden md:table-cell">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600">{{ $perm->slug }}</span>
                                </td>
                                <td class="text-slate-500">{{ $perm->group }}</td>
                                <td class="text-center">
                                    @if ($perm->roles_count > 0)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $perm->roles_count }} role</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" @click="openEdit(@js($permPayload))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        <button type="button" title="Hapus" @click="confirmDelete({{ $perm->id }}, @js($perm->name))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="5" title="Permission tidak ditemukan" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$permissions" label="permission" />
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="modalOpen" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-start justify-center p-4 sm:items-center">
                <div x-show="modalOpen" x-transition class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
                    <form method="POST" :action="formAction" x-ref="permForm">
                        @csrf
                        <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                        <input type="hidden" name="permission_id" :value="permId">

                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'edit' ? 'Edit Permission' : 'Tambah Permission'"></h3>
                                    <p class="text-xs text-slate-400">Definisikan hak akses</p>
                                </div>
                            </div>
                            <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-4 px-6 py-5">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Permission</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Slug <span class="text-slate-400">(opsional)</span></label>
                                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="cth: inventory.view"
                                        class="block w-full rounded-xl border-slate-200 font-mono text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                    @error('slug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Grup</label>
                                    <select name="group" class="select2-group w-full">
                                        @foreach ($groups as $g)
                                            <option value="{{ $g }}" @selected(old('group') === $g)>{{ $g }}</option>
                                        @endforeach
                                    </select>
                                    @error('group') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
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
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Hapus Permission?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Menghapus <span class="font-semibold text-slate-700" x-text="deleteName"></span> juga mencabutnya dari semua role terkait.
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
            function permissionsPage(config) {
                return {
                    modalOpen: false,
                    deleteOpen: false,
                    mode: 'create',
                    permId: null,
                    formAction: '{{ route('permissions.store') }}',
                    deleteName: '',
                    deleteAction: '',

                    init() {
                        if (config.openOnLoad) {
                            this.mode = config.editingOnLoad ? 'edit' : 'create';
                            this.permId = '{{ old('permission_id') }}';
                            this.formAction = this.mode === 'edit'
                                ? '{{ url('permissions') }}/' + this.permId
                                : '{{ route('permissions.store') }}';
                            this.$nextTick(() => { this.modalOpen = true; });
                        }
                    },

                    openCreate() {
                        this.mode = 'create';
                        this.permId = null;
                        this.formAction = '{{ route('permissions.store') }}';
                        const f = this.$refs.permForm;
                        f.name.value = '';
                        f.slug.value = '';
                        f.description.value = '';
                        $(f.group).val(null).trigger('change');
                        this.modalOpen = true;
                    },

                    openEdit(p) {
                        this.mode = 'edit';
                        this.permId = p.id;
                        this.formAction = '{{ url('permissions') }}/' + p.id;
                        const f = this.$refs.permForm;
                        f.name.value = p.name ?? '';
                        f.slug.value = p.slug ?? '';
                        f.description.value = p.description ?? '';
                        this.setGroup(p.group);
                        this.modalOpen = true;
                    },

                    setGroup(group) {
                        const $sel = $(this.$refs.permForm.group);
                        if (group && $sel.find("option[value='" + group + "']").length === 0) {
                            $sel.append(new Option(group, group, true, true));
                        }
                        $sel.val(group).trigger('change');
                    },

                    closeModal() { this.modalOpen = false; },

                    confirmDelete(id, name) {
                        this.deleteName = name;
                        this.deleteAction = '{{ url('permissions') }}/' + id;
                        this.deleteOpen = true;
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('permFilterForm');
                $('.filter-select').each(function () {
                    $(this).select2({ minimumResultsForSearch: Infinity, width: '100%' })
                        .on('change', function () { form.submit(); });
                });

                const $shell = $('[x-ref="permForm"]').closest('.rounded-xl');
                $('.select2-group').select2({
                    tags: true,
                    dropdownParent: $shell,
                    width: '100%',
                    placeholder: 'Pilih / ketik grup',
                });
            });
        </script>
    @endpush
</x-app-layout>
