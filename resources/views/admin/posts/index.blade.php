<x-app-layout>
    <x-slot name="title">{{ __('Posts') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Posts') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-chat-square-text me-1 text-edu" aria-hidden="true"></i> {{ __('Posts') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Hide or delete inappropriate posts (FR-28).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                {{ $posts->total() }} {{ __('posts') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="post-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="post-filters-title">{{ __('Filter posts') }}</h2>

                <form method="GET" action="{{ route('admin.posts.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label for="post-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="post-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Post text or author name') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="post-hidden" class="form-label small">{{ __('Visibility') }}</label>
                        <select name="hidden" id="post-hidden" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($hidden === '0')>{{ __('Visible') }}</option>
                            <option value="1" @selected($hidden === '1')>{{ __('Hidden') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by visibility') }}">
                    <a class="edu-filter-chip {{ $hidden === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term])) }}">{{ __('All') }}</a>
                    <a class="edu-filter-chip {{ $hidden === '0' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term, 'hidden' => '0'])) }}">{{ __('Visible') }}</a>
                    <a class="edu-filter-chip {{ $hidden === '1' ? 'is-active' : '' }}"
                       href="{{ route('admin.posts.index', array_filter(['q' => $term, 'hidden' => '1'])) }}">{{ __('Hidden') }}</a>
                </div>
            </div>
        </section>

        @if ($posts->isEmpty())
            <x-empty-state icon="bi-inbox"
                           :title="__('No posts match these filters.')"
                           :message="__('Try a different search or visibility, or reset the filters.')">
                <a href="{{ route('admin.posts.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All posts, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Author') }}</th>
                                <th scope="col">{{ __('Post') }}</th>
                                <th scope="col" class="text-end">{{ __('Reports') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Posted') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr>
                                    <td data-label="{{ __('Author') }}" class="text-nowrap">
                                        <a href="{{ route('users.show', $post->user) }}" class="text-decoration-none text-reset">
                                            {{ $post->user?->name ?? __('Deleted user') }}
                                        </a>
                                    </td>
                                    <td data-label="{{ __('Post') }}" class="edu-admin-post-cell">
                                        <a href="{{ route('posts.show', $post) }}" class="text-decoration-none text-reset">
                                            {{ \Illuminate\Support\Str::limit($post->body, 120) }}
                                        </a>
                                        @if ($post->image)
                                            <i class="bi bi-image text-muted ms-1" title="{{ __('Has an image') }}"
                                               aria-label="{{ __('Has an image') }}"></i>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Reports') }}" class="text-end">
                                        @if ($post->reports_count > 0)
                                            <span class="badge text-bg-warning">{{ $post->reports_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        @if ($post->is_hidden)
                                            <span class="badge text-bg-warning">{{ __('Hidden') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Visible') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Posted') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $post->created_at?->toIso8601String() }}">{{ $post->created_at?->format('d M Y H:i') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route('admin.posts.hide', $post) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $post->is_hidden ? 'success' : 'warning' }}">
                                                    <i class="bi bi-eye{{ $post->is_hidden ? '' : '-slash' }} me-1" aria-hidden="true"></i>
                                                    {{ $post->is_hidden ? __('Unhide') : __('Hide') }}
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#delete-post-admin-{{ $post->id }}"
                                                    title="{{ __('Delete') }}" aria-label="{{ __('Delete this post') }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($posts->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$posts" />
                    </div>
                @endif
            </div>

            @foreach ($posts as $post)
                <x-confirm-delete-modal
                    :id="'delete-post-admin-'.$post->id"
                    :action="route('admin.posts.destroy', $post)"
                    :title="__('Delete this post?')"
                    :message="__('The post, its image, comments, likes and reports are deleted. This cannot be undone.')"
                    :confirm-label="__('Delete post')"
                    :show-trigger="false" />
            @endforeach
        @endif
    </div>
</x-app-layout>
