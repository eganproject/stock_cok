<x-app-layout>
    <x-slot name="title">Role</x-slot>
    <x-slot name="subtitle">Kelola peran dan hak akses pengguna</x-slot>

    @php
        $hasErrors = $errors->any();
        $editing   = old('_method') === 'PUT' && old('role_id');
        $oldPerms  = collect(old('permissions', []))->map(fn ($v) => (int) $v)->all();
        $hasFilter = request()->hasAny(['search', 'type']);
    @endphp

    <div x-data="rolesPage({
            openOnLoad: {{ $hasErrors ? 'true' : 'false' }},
            editingOnLoad: {{ $editing ? 'true' : 'false' }},
            rolePermissions: @js($rolePermissions),
        })">

        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @php
                $roleCards = [
                    ['label' => 'Total Role', 'value' => $stats['total'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Z"/>'],
                    ['label' => 'Role Sistem', 'value' => $stats['locked'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>'],
                    ['label' => 'Total Permission', 'value' => $stats['permissions'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>'],
                ];
            @endphp
            @foreach ($roleCards as $c)
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
                    <h3 class="text-base font-semibold text-slate-900">Daftar Role</h3>
                    <p class="text-sm text-slate-400">Atur peran dan hak akses per fitur</p>
                </div>
                <button @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Role
                </button>
            </div>

            <!-- Filter toolbar -->
            <form method="GET" action="{{ route('roles.index') }}" id="roleFilterForm"
                  class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative sm:w-72">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari role..."
                            class="block w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <div class="sm:w-44">
                        <select name="type" class="filter-select w-full">
                            <option value="">Semua tipe</option>
                            <option value="system" @selected(request('type')==='system')>Role Sistem</option>
                            <option value="custom" @selected(request('type')==='custom')>Role Kustom</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">Terapkan</button>
                    @if ($hasFilter)
                        <a href="{{ route('roles.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
                    @endif
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-500">
                    Tampil
                    <select name="per_page" onchange="document.getElementById('roleFilterForm').submit()"
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
                            <x-th-sort column="name" :sort="$sort" :direction="$direction">Role</x-th-sort>
                            <th class="hidden md:table-cell">Deskripsi</th>
                            <x-th-sort column="permissions_count" :sort="$sort" :direction="$direction" align="center" class="text-center">Permission</x-th-sort>
                            <x-th-sort column="is_locked" :sort="$sort" :direction="$direction" align="center" class="text-center">Tipe</x-th-sort>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            @php
                                $rolePayload = [
                                    'id'          => $role->id,
                                    'name'        => $role->name,
                                    'description' => $role->description,
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold uppercase text-white">
                                            {{ substr($role->name, 0, 1) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">{{ $role->name }}</p>
                                            <p class="truncate font-mono text-xs text-slate-400">{{ $role->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden text-slate-500 md:table-cell">{{ $role->description ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $role->permissions_count }} akses</span>
                                </td>
                                <td class="text-center">
                                    @if ($role->is_locked)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                            Sistem
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Kustom</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" @click="openEdit(@js($rolePayload))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        @unless ($role->is_locked)
                                            <button type="button" title="Hapus" @click="confirmDelete({{ $role->id }}, @js($role->name))"
                                                class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                                            </button>
                                        @else
                                            <span class="inline-block w-[34px]"></span>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="5" title="Role tidak ditemukan" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$roles" label="role" />
        </div>

        @include('roles.partials.form-modal')

        <!-- Delete Modal -->
        <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="deleteOpen" x-transition.opacity @click="deleteOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="deleteOpen" x-transition class="relative w-full max-w-md rounded-xl bg-white p-6 text-center shadow-2xl">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Hapus Role?</h3>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Anda akan menghapus role <span class="font-semibold text-slate-700" x-text="deleteName"></span>. Tindakan ini tidak dapat dibatalkan.
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
            function rolesPage(config) {
                return {
                    modalOpen: false,
                    deleteOpen: false,
                    mode: 'create',
                    roleId: null,
                    formAction: '{{ route('roles.store') }}',
                    deleteName: '',
                    deleteAction: '',
                    selectedCount: 0,
                    rolePermissions: config.rolePermissions || {},

                    init() {
                        this.refreshCount();
                        if (config.openOnLoad) {
                            this.mode = config.editingOnLoad ? 'edit' : 'create';
                            this.roleId = '{{ old('role_id') }}';
                            this.formAction = this.mode === 'edit'
                                ? '{{ url('roles') }}/' + this.roleId
                                : '{{ route('roles.store') }}';
                            this.$nextTick(() => { this.modalOpen = true; this.refreshCount(); });
                        }
                    },

                    perms() { return Array.from(this.$refs.roleForm.querySelectorAll('[data-perm]')); },

                    openCreate() {
                        this.mode = 'create';
                        this.roleId = null;
                        this.formAction = '{{ route('roles.store') }}';
                        this.$refs.roleForm.name.value = '';
                        this.$refs.roleForm.description.value = '';
                        this.setPerms([]);
                        this.modalOpen = true;
                    },

                    openEdit(role) {
                        this.mode = 'edit';
                        this.roleId = role.id;
                        this.formAction = '{{ url('roles') }}/' + role.id;
                        this.$refs.roleForm.name.value = role.name ?? '';
                        this.$refs.roleForm.description.value = role.description ?? '';
                        this.setPerms(this.rolePermissions[role.id] || []);
                        this.modalOpen = true;
                    },

                    setPerms(ids) {
                        const set = new Set(ids.map(Number));
                        this.perms().forEach(cb => { cb.checked = set.has(Number(cb.value)); });
                        this.refreshCount();
                    },

                    toggleAll(state) {
                        this.perms().forEach(cb => { cb.checked = state; });
                        this.refreshCount();
                    },

                    toggleGroup(groupIdx, state) {
                        this.$refs.roleForm.querySelectorAll('[data-perm][data-group="' + groupIdx + '"]')
                            .forEach(cb => { cb.checked = state; });
                        this.refreshCount();
                    },

                    syncGroupToggles() {
                        this.$refs.roleForm.querySelectorAll('[data-grouptoggle]').forEach(g => {
                            const idx = g.getAttribute('data-grouptoggle');
                            const boxes = Array.from(this.$refs.roleForm.querySelectorAll('[data-perm][data-group="' + idx + '"]'));
                            g.checked = boxes.length > 0 && boxes.every(b => b.checked);
                        });
                    },

                    refreshCount() {
                        this.selectedCount = this.perms().filter(cb => cb.checked).length;
                        this.syncGroupToggles();
                    },

                    closeModal() { this.modalOpen = false; },

                    confirmDelete(id, name) {
                        this.deleteName = name;
                        this.deleteAction = '{{ url('roles') }}/' + id;
                        this.deleteOpen = true;
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('roleFilterForm');
                $('.filter-select').each(function () {
                    $(this).select2({ minimumResultsForSearch: Infinity, width: '100%' })
                        .on('change', function () { form.submit(); });
                });
            });
        </script>
    @endpush
</x-app-layout>
