<div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div x-show="deleteOpen" x-transition.opacity @click="deleteOpen = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="deleteOpen" x-transition class="relative w-full max-w-md rounded-xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-slate-900">Hapus Pengguna?</h3>
            <p class="mt-1.5 text-sm text-slate-500">
                Anda akan menghapus <span class="font-semibold text-slate-700" x-text="deleteName"></span>. Tindakan ini tidak dapat dibatalkan.
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
