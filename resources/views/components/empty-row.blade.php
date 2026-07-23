@props([
    'colspan' => 1,
    'title' => 'Belum ada data',
    'message' => 'Coba ubah kata kunci pencarian atau filter yang dipakai.',
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-16 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5M12 12v9M3.75 7.5v9L12 21"/>
            </svg>
        </div>
        <p class="mt-3 text-sm font-medium text-slate-700">{{ $title }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ $message }}</p>
    </td>
</tr>
