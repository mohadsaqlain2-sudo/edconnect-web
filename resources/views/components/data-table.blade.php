{{-- Data table wrapper with client-side search and sorting.
     Server-side pagination stays in the page and is passed through the footer
     slot, so this component only filters/sorts the rows of the current page.

     Usage:
       <x-data-table id="teacher-classes" title="My classes" :count="$classes->total()">
           <x-slot name="head"><tr><th data-sort>Title</th>...</tr></x-slot>
           <tr data-table-row data-search="...">...</tr>
           <x-slot name="footer"><x-pagination-links :paginator="$classes" /></x-slot>
       </x-data-table> --}}
@props([
    'id' => null,
    'title' => null,
    'subtitle' => null,
    'searchPlaceholder' => null,
    'count' => null,
    'countLabel' => null,
])

@php
    $id = $id ?: 'table-'.substr(md5($title ?? 'data-table'), 0, 8);
    $searchPlaceholder = $searchPlaceholder ?: __('Search…');
    $countLabel = $countLabel ?: __(':count rows');
@endphp

<div class="card edu-card edu-data-table" data-data-table data-count-label="{{ $countLabel }}">
    <div class="card-header">
        <div class="min-w-0">
            @if ($title)
                <h2 class="card-title h6 fw-bold mb-0">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <div class="card-subtitle small text-muted">{{ $subtitle }}</div>
            @endif
            @isset($header)
                {{ $header }}
            @endisset
        </div>

        <div class="edu-table-tools">
            <div class="edu-table-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="{{ $id }}-search">{{ $searchPlaceholder }}</label>
                <input type="search" class="form-control form-control-sm" id="{{ $id }}-search"
                       data-table-search placeholder="{{ $searchPlaceholder }}">
            </div>

            @isset($tools)
                {{ $tools }}
            @endisset
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                {{ $head }}
            </thead>
            <tbody data-table-body>
                {{ $slot }}
            </tbody>
        </table>

        <div class="edu-table-empty" data-table-empty hidden>
            <i class="bi bi-search me-1" aria-hidden="true"></i>
            {{ __('Nothing matches your search on this page.') }}
        </div>
    </div>

    @if (isset($footer) || $count !== null)
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="edu-table-count" data-table-count>
                {{ $count !== null ? $count : '' }}
            </span>

            @isset($footer)
                {{ $footer }}
            @endisset
        </div>
    @endif
</div>
