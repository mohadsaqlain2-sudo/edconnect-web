<x-app-layout>
    <x-slot name="title">{{ __('Report #:id', ['id' => $report->id]) }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">{{ __('Reports') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $report->id }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-flag me-1 text-edu" aria-hidden="true"></i> {{ __('Report #:id', ['id' => $report->id]) }}
                </h1>
                <p class="small text-muted mb-0">
                    {{ $report->reason_label }}
                    <span aria-hidden="true">&middot;</span>
                    {{ $report->created_at?->format('d M Y H:i') }}
                </p>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> {{ __('Back to reports') }}
            </a>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        @php($content = $report->reportable)

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                {{-- The reported content --}}
                <section class="card edu-card mb-4" aria-labelledby="reported-content-title">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h2 class="h6 fw-bold mb-0" id="reported-content-title">{{ __('Reported content') }}</h2>
                            <span class="badge bg-body-secondary text-body border">{{ $report->content_type }}</span>
                            <x-status-badge :status="$report->status" />
                            @if ($content && $content->is_hidden)
                                <span class="badge text-bg-warning">{{ __('hidden') }}</span>
                            @endif
                        </div>

                        @if ($content)
                            <span class="small text-muted">
                                {{ __('by') }} {{ $content->user?->name ?? __('deleted user') }}
                            </span>
                        @endif
                    </div>

                    <div class="card-body">
                        @if (! $content)
                            <x-empty-state icon="bi-trash"
                                           :title="__('The reported content has been deleted.')"
                                           :message="__('Nothing left to moderate. You can still review or dismiss the report.')" />
                        @else
                            <p class="post-body mb-3 text-break">{{ $content->body }}</p>

                            @if ($content instanceof \App\Models\Post && $content->image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content->image) }}"
                                     alt="{{ __('Reported image') }}" class="img-fluid rounded mb-3"
                                     loading="lazy" decoding="async">
                            @endif

                            @if ($content instanceof \App\Models\Comment)
                                <a href="{{ route('posts.show', $content->post_id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open the post') }}
                                </a>
                            @else
                                <a href="{{ route('posts.show', $content) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ __('Open the post') }}
                                </a>
                            @endif
                        @endif
                    </div>
                </section>

                {{-- Other reports about the same content --}}
                <section class="card edu-card" aria-labelledby="related-reports-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="related-reports-title">{{ __('Other reports on this content') }}</h2>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($related as $other)
                            <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="small min-w-0">
                                    <span class="fw-semibold">{{ $other->reporter?->name ?? __('Deleted user') }}</span>
                                    <span aria-hidden="true">&middot;</span> {{ $other->reason_label }}
                                    <div class="text-muted">{{ $other->created_at?->diffForHumans() }}</div>
                                </div>
                                <x-status-badge :status="$other->status" />
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted small py-4">
                                {{ __('No other reports for this content.') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <div class="edu-sticky-aside">
                    {{-- Report details --}}
                    <section class="card edu-card mb-4" aria-labelledby="report-details-title">
                        <div class="card-header">
                            <h2 class="h6 fw-bold mb-0" id="report-details-title">{{ __('Report details') }}</h2>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reporter') }}</span>
                                <span class="fw-semibold text-end text-break">{{ $report->reporter?->name ?? __('Deleted') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reported user') }}</span>
                                <span class="fw-semibold text-end text-break">{{ $report->reported_user?->name ?? __('Deleted') }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between gap-3">
                                <span class="text-muted">{{ __('Reason') }}</span>
                                <span class="fw-semibold text-end">{{ $report->reason_label }}</span>
                            </li>
                            <li class="list-group-item">
                                <div class="text-muted mb-1">{{ __('Details') }}</div>
                                <div class="small text-break">{{ $report->details ?: __('No extra details were given.') }}</div>
                            </li>
                        </ul>
                    </section>

                    {{-- Moderation actions (FR-33) --}}
                    <section class="card edu-card" aria-labelledby="moderation-title">
                        <div class="card-header">
                            <h2 class="h6 fw-bold mb-0" id="moderation-title">{{ __('Actions') }}</h2>
                        </div>
                        <div class="card-body d-flex flex-column gap-2">
                            @if ($content)
                                <form method="POST" action="{{ route('admin.reports.hide', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-warning w-100" @disabled($content->is_hidden)>
                                        <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> {{ __('Hide the content') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.reports.unhide', $report) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-warning w-100" @disabled(! $content->is_hidden)>
                                        <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ __('Unhide the content') }}
                                    </button>
                                </form>

                                <button type="button" class="btn btn-danger w-100"
                                        data-bs-toggle="modal" data-bs-target="#delete-report-content-{{ $report->id }}">
                                    <i class="bi bi-trash me-1" aria-hidden="true"></i> {{ __('Delete the content') }}
                                </button>
                            @endif

                            @if ($report->reported_user)
                                <button type="button" class="btn btn-outline-danger w-100"
                                        data-bs-toggle="modal" data-bs-target="#block-report-user-{{ $report->id }}">
                                    <i class="bi bi-person-slash me-1" aria-hidden="true"></i>
                                    {{ __('Block :name', ['name' => $report->reported_user->name]) }}
                                </button>
                            @endif

                            <hr class="my-1">

                            <form method="POST" action="{{ route('admin.reports.reviewed', $report) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-success w-100" @disabled($report->status === \App\Models\Report::STATUS_REVIEWED)>
                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> {{ __('Mark as reviewed') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-secondary w-100" @disabled($report->status === \App\Models\Report::STATUS_DISMISSED)>
                                    <i class="bi bi-x-circle me-1" aria-hidden="true"></i> {{ __('Dismiss the report') }}
                                </button>
                            </form>
                        </div>
                    </section>

                    {{-- Destructive actions confirm in a Bootstrap modal. --}}
                    @if ($content)
                        <x-confirm-delete-modal
                            :id="'delete-report-content-'.$report->id"
                            :action="route('admin.reports.content.destroy', $report)"
                            :title="__('Delete this content?')"
                            :message="__('The content and every report about it are deleted. This cannot be undone.')"
                            :confirm-label="__('Delete content')"
                            :show-trigger="false" />
                    @endif

                    @if ($report->reported_user)
                        <x-confirm-delete-modal
                            :id="'block-report-user-'.$report->id"
                            :action="route('admin.reports.block-user', $report)"
                            method="POST"
                            :title="__('Block :name?', ['name' => $report->reported_user->name])"
                            :message="__('They will be logged out immediately and cannot sign in until an administrator unblocks them.')"
                            :confirm-label="__('Block account')"
                            confirm-class="btn btn-warning"
                            :show-trigger="false" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
