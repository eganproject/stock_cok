<div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div x-show="modalOpen" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-start justify-center p-4 sm:items-center">
        <div x-show="modalOpen" x-transition class="relative w-full max-w-lg rounded-xl bg-white shadow-2xl">
            <form method="POST" :action="formAction" x-ref="userForm">
                @csrf
                <input type="hidden" name="_method" :value="mode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="user_id" :value="userId">

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900" x-text="mode === 'edit' ? 'Edit Pengguna' : 'Tambah Pengguna'"></h3>
                            <p class="text-xs text-slate-400" x-text="mode === 'edit' ? 'Perbarui data pengguna' : 'Buat akun pengguna baru'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="max-h-[65vh] space-y-4 overflow-y-auto px-6 py-5 scrollbar-thin">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">No. Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                            @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Peran</label>
                            <select name="role" class="select2-modal w-full">
                                <option value="staff"   @selected(old('role')==='staff')>Staff</option>
                                <option value="manager" @selected(old('role')==='manager')>Manager</option>
                                <option value="admin"   @selected(old('role')==='admin')>Administrator</option>
                            </select>
                            @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" class="select2-modal w-full">
                                <option value="active"   @selected(old('status')==='active')>Aktif</option>
                                <option value="inactive" @selected(old('status')==='inactive')>Nonaktif</option>
                            </select>
                            @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal Bergabung</label>
                            <input type="text" name="joined_at" value="{{ old('joined_at') }}" placeholder="Pilih tanggal"
                                class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                            @error('joined_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <span x-show="mode === 'create'">Kata Sandi</span>
                            <span x-show="mode === 'edit'" x-cloak>Ubah Kata Sandi (opsional)</span>
                        </p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                                <input type="password" name="password" autocomplete="new-password" :required="mode === 'create'"
                                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi</label>
                                <input type="password" name="password_confirmation" autocomplete="new-password" :required="mode === 'create'"
                                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-slate-900 focus:ring-2 focus:ring-slate-900/15">
                            </div>
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
