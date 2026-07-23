@props([
    'column',
    'sort' => null,
    'direction' => 'asc',
    'align' => 'left',
])

@php
    $isActive = $sort === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDirection,
        'page' => 1,
    ]);
    $justify = ['left' => 'justify-start', 'center' => 'justify-center', 'right' => 'justify-end'][$align] ?? 'justify-start';
@endphp

<th {{ $attributes }}>
    <a href="{{ $url }}"
       class="group inline-flex w-full items-center gap-1 {{ $justify }} {{ $isActive ? 'text-slate-900' : 'hover:text-slate-700' }}">
        <span>{{ $slot }}</span>
        @if ($isActive)
            @if ($direction === 'asc')
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/></svg>
            @else
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            @endif
        @else
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 opacity-0 transition group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>
        @endif
    </a>
</th>
