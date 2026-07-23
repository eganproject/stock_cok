@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        {{-- Mobile: prev / next only --}}
        <div class="flex flex-1 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="cursor-default rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-300">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Sebelumnya</a>
            @endif

            <span class="text-xs text-slate-400">Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Berikutnya</a>
            @else
                <span class="cursor-default rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-300">Berikutnya</span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-end">
            <span class="isolate inline-flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
                       class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-900 hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </a>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="flex h-9 w-9 items-center justify-center text-sm text-slate-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-slate-900 px-2 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 px-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"
                       class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-900 hover:text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </a>
                @else
                    <span aria-disabled="true" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
