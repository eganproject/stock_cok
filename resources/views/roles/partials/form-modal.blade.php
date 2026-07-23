<div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div x-show="modalOpen" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-start justify-center p-4 sm:items-center">
        <div x-show="modalOpen" x-transition class="relative w-full max-w-2xl rounded-xl bg-white shadow-2xl">
            <form method="POST" :action="formAction" x-ref="roleForm">
                @csrf
                <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="role_id" :value="roleId">

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'edit' ? 'Edit Role' : 'Tambah Role'"></h3>
                            <p class="text-xs text-slate-400">Tentukan nama dan hak akses</p>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="max-h-[68vh] space-y-5 overflow-y-auto px-6 py-5 scrollbar-thin">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Role</label>
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

                    <div>
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <label class="text-sm font-medium text-slate-700">Hak Akses (Permission)</label>
                            <div class="flex items-center gap-3 text-xs">
                                <span class="text-slate-400"><span x-text="selectedCount"></span> dipilih</span>
                                <button type="button" @click="toggleAll(true)" class="font-medium text-slate-600 hover:text-slate-900">Pilih semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="toggleAll(false)" class="font-medium text-slate-600 hover:text-slate-900">Kosongkan</button>
                            </div>
                        </div>
                        @error('permissions') <p class="mb-2 text-xs text-rose-600">{{ $message }}</p> @enderror

                        <div class="space-y-3">
                            @foreach ($permissions as $group => $perms)
                                <div class="rounded-xl border border-slate-200">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <label class="flex items-center gap-2.5 text-sm font-semibold text-slate-800">
                                            <input type="checkbox" data-grouptoggle="{{ $loop->index }}"
                                                @change="toggleGroup({{ $loop->index }}, $event.target.checked)"
                                                class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/30">
                                            {{ $group }}
                                        </label>
                                        <span class="text-xs text-slate-400">{{ $perms->count() }} akses</span>
                                    </div>
                                    <div class="grid grid-cols-1 gap-x-4 gap-y-2 border-t border-slate-100 px-4 py-3 sm:grid-cols-2">
                                        @foreach ($perms as $perm)
                                            <label class="flex cursor-pointer items-start gap-2.5">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                    data-perm data-group="{{ $loop->parent->index }}"
                                                    @change="refreshCount()"
                                                    @checked(in_array($perm->id, $oldPerms))
                                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/30">
                                                <span class="leading-tight">
                                                    <span class="block text-sm text-slate-700">{{ $perm->name }}</span>
                                                    <span class="block font-mono text-[11px] text-slate-400">{{ $perm->slug }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
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
