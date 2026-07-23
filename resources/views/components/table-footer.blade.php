@props(['paginator', 'label' => 'data'])

<div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
    <p class="text-xs text-slate-500">
        @if ($paginator->total() > 0)
            Menampilkan <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
            dari <span class="font-medium text-slate-700">{{ number_format($paginator->total(), 0, ',', '.') }}</span> {{ $label }}
        @else
            Tidak ada {{ $label }} yang cocok
        @endif
    </p>

    <div>{{ $paginator->links() }}</div>
</div>
