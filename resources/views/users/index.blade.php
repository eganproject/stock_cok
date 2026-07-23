<x-app-layout>
    <x-slot name="title">Manajemen User</x-slot>
    <x-slot name="subtitle">Kelola akun dan hak akses pengguna</x-slot>

    @php
        $hasErrors = $errors->any();
        $editing   = old('_method') === 'PUT' && old('user_id');
        $formAction = $editing ? route('users.update', old('user_id')) : route('users.store');
        $hasFilter = request()->hasAny(['search', 'role', 'status', 'joined_from', 'joined_to']);
    @endphp

    <div x-data="usersPage({ openOnLoad: {{ $hasErrors ? 'true' : 'false' }}, editingOnLoad: {{ $editing ? 'true' : 'false' }} })">

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            @php
                $userCards = [
                    ['label' => 'Total User', 'value' => $stats['total'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>'],
                    ['label' => 'Aktif', 'value' => $stats['active'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'],
                    ['label' => 'Nonaktif', 'value' => $stats['inactive'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>'],
                    ['label' => 'Administrator', 'value' => $stats['admins'],
                     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>'],
                ];
            @endphp
            @foreach ($userCards as $c)
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
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Daftar Pengguna</h3>
                    <p class="text-sm text-slate-400">Cari, urutkan, dan kelola akun pengguna</p>
                </div>
                <button @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah User
                </button>
            </div>

            <!-- Filter toolbar (server-side, GET) -->
            <form method="GET" action="{{ route('users.index') }}" id="userFilterForm"
                  class="border-b border-slate-100 px-4 py-4 sm:px-6">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <!-- Search -->
                    <div class="lg:col-span-4">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari nama, email, telepon..."
                                class="block w-full rounded-xl border-slate-200 py-2.5 pl-9 pr-3 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="lg:col-span-2">
                        <select name="role" class="filter-select w-full" data-placeholder="Semua peran">
                            <option value="">Semua peran</option>
                            <option value="admin" @selected(request('role')==='admin')>Administrator</option>
                            <option value="manager" @selected(request('role')==='manager')>Manager</option>
                            <option value="staff" @selected(request('role')==='staff')>Staff</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="lg:col-span-2">
                        <select name="status" class="filter-select w-full" data-placeholder="Semua status">
                            <option value="">Semua status</option>
                            <option value="active" @selected(request('status')==='active')>Aktif</option>
                            <option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option>
                        </select>
                    </div>

                    <!-- Joined range -->
                    <div class="lg:col-span-2">
                        <input type="text" id="joinedFrom" name="joined_from" value="{{ request('joined_from') }}" placeholder="Bergabung dari"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                    <div class="lg:col-span-2">
                        <input type="text" id="joinedTo" name="joined_to" value="{{ request('joined_to') }}" placeholder="Bergabung s/d"
                            class="block w-full rounded-xl border-slate-200 py-2.5 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-medium text-white hover:bg-slate-800">
                            Terapkan
                        </button>
                        @if ($hasFilter)
                            <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                Reset
                            </a>
                        @endif
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-500">
                        Tampil
                        <select name="per_page" onchange="document.getElementById('userFilterForm').submit()"
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
                <table class="tbl min-w-[720px]">
                    <thead>
                        <tr>
                            <x-th-sort column="name" :sort="$sort" :direction="$direction">Pengguna</x-th-sort>
                            <th class="hidden md:table-cell">Kontak</th>
                            <x-th-sort column="role" :sort="$sort" :direction="$direction">Peran</x-th-sort>
                            <x-th-sort column="status" :sort="$sort" :direction="$direction">Status</x-th-sort>
                            <x-th-sort column="joined_at" :sort="$sort" :direction="$direction" class="hidden lg:table-cell">Bergabung</x-th-sort>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $roleBadge = [
                                    'admin'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    'manager' => 'bg-slate-900 text-white ring-slate-900/20',
                                    'staff'   => 'bg-slate-100 text-slate-600 ring-slate-500/20',
                                ][$user->role] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20';

                                $userPayload = [
                                    'id'        => $user->id,
                                    'name'      => $user->name,
                                    'email'     => $user->email,
                                    'role'      => $user->role,
                                    'phone'     => $user->phone,
                                    'status'    => $user->status,
                                    'joined_at' => optional($user->joined_at)->format('Y-m-d'),
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">{{ $user->name }}</p>
                                            <p class="truncate text-xs text-slate-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden text-slate-500 md:table-cell">{{ $user->phone ?? '—' }}</td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset {{ $roleBadge }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td>
                                    @if ($user->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="hidden text-slate-500 lg:table-cell">
                                    {{ optional($user->joined_at)->translatedFormat('d M Y') ?? '—' }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" @click="openEdit(@js($userPayload))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        <button type="button" title="Hapus" @click="confirmDelete({{ $user->id }}, @js($user->name))"
                                            class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="6" title="Pengguna tidak ditemukan" />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-footer :paginator="$users" label="pengguna" />
        </div>

        @include('users.partials.form-modal', ['formAction' => $formAction])
        @include('users.partials.delete-modal')
    </div>

    @push('scripts')
        <script>
            let usersJoinedFp = null;

            function usersPage(config) {
                return {
                    modalOpen: false,
                    deleteOpen: false,
                    mode: 'create',
                    userId: null,
                    formAction: '{{ route('users.store') }}',
                    deleteName: '',
                    deleteAction: '',

                    init() {
                        if (config.openOnLoad) {
                            this.mode = config.editingOnLoad ? 'edit' : 'create';
                            this.userId = '{{ old('user_id') }}';
                            this.formAction = '{{ $formAction }}';
                            this.$nextTick(() => { this.modalOpen = true; });
                        }
                    },

                    openCreate() {
                        this.mode = 'create';
                        this.userId = null;
                        this.formAction = '{{ route('users.store') }}';
                        this.resetForm();
                        this.modalOpen = true;
                    },

                    openEdit(user) {
                        this.mode = 'edit';
                        this.userId = user.id;
                        this.formAction = '{{ url('users') }}/' + user.id;
                        this.resetForm();
                        const f = this.$refs.userForm;
                        f.name.value = user.name ?? '';
                        f.email.value = user.email ?? '';
                        f.phone.value = user.phone ?? '';
                        $(f.role).val(user.role).trigger('change');
                        $(f.status).val(user.status).trigger('change');
                        if (usersJoinedFp) usersJoinedFp.setDate(user.joined_at || null, true);
                        this.modalOpen = true;
                    },

                    resetForm() {
                        const f = this.$refs.userForm;
                        if (!f) return;
                        f.name.value = '';
                        f.email.value = '';
                        f.phone.value = '';
                        f.password.value = '';
                        f.password_confirmation.value = '';
                        $(f.role).val('staff').trigger('change');
                        $(f.status).val('active').trigger('change');
                        if (usersJoinedFp) usersJoinedFp.clear();
                    },

                    closeModal() { this.modalOpen = false; },

                    confirmDelete(id, name) {
                        this.deleteName = name;
                        this.deleteAction = '{{ url('users') }}/' + id;
                        this.deleteOpen = true;
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('userFilterForm');

                // Select2 filters — auto submit on change
                $('.filter-select').each(function () {
                    $(this).select2({ minimumResultsForSearch: Infinity, width: '100%' })
                        .on('change', function () { form.submit(); });
                });

                // Date filters — submit when a date is picked
                ['#joinedFrom', '#joinedTo'].forEach(function (sel) {
                    flatpickr(sel, {
                        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y',
                        allowInput: false, locale: { firstDayOfWeek: 1 },
                        onClose: function (dates, str, inst) {
                            if (str !== inst.input.defaultValue) form.submit();
                        },
                    });
                });

                // Modal Select2 + Flatpickr
                const modalForm = document.querySelector('[x-ref="userForm"]');
                const $shell = $(modalForm).closest('.rounded-xl');
                $('.select2-modal').select2({ minimumResultsForSearch: Infinity, dropdownParent: $shell, width: '100%' });
                usersJoinedFp = flatpickr(modalForm.querySelector('[name="joined_at"]'), {
                    dateFormat: 'Y-m-d', altInput: true, altFormat: 'd F Y',
                    maxDate: 'today', locale: { firstDayOfWeek: 1 },
                });
            });
        </script>
    @endpush
</x-app-layout>
