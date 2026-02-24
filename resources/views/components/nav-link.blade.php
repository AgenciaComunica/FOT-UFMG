@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition'
            : 'inline-flex items-center rounded-xl border border-transparent px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
