{{-- Bootstrap 5 pagination links, hidden when everything fits on one page.
     The Bootstrap views are switched on in AppServiceProvider.
     Usage: <x-pagination-links :paginator="$classes" /> --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="edu-pagination mt-3 d-flex justify-content-center" aria-label="{{ __('Page navigation') }}">
        {{ $paginator->links() }}
    </nav>
@endif
