<x-app-layout>
    <x-slot name="title">{{ __('Style guide') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Style guide') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-palette me-1 text-edu" aria-hidden="true"></i>
                    {{ __('Style guide') }}
                </h1>
                <p class="small text-muted mb-0">
                    {{ __('Single source of truth for colour, type, spacing and every component state.') }}
                </p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-theme-toggle>
                <i class="bi bi-circle-half me-1" aria-hidden="true"></i>
                {{ __('Toggle dark mode') }}
            </button>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <div class="row g-4">
            {{-- In-page navigation --}}
            <div class="col-12 col-xl-3">
                <nav class="card edu-card position-sticky" style="top: calc(var(--edu-navbar-height) + 1rem);"
                     aria-label="{{ __('Style guide sections') }}">
                    <div class="card-body py-3">
                        <div class="edu-eyebrow mb-2">{{ __('On this page') }}</div>
                        <ul class="nav flex-column gap-1 small">
                            @foreach ([
                                'tokens' => __('Tokens'),
                                'typography' => __('Typography'),
                                'buttons' => __('Buttons'),
                                'forms' => __('Forms'),
                                'badges' => __('Badges'),
                                'cards' => __('Cards & empty states'),
                                'tables' => __('Tables'),
                                'feedback' => __('Modals, toasts & dropdowns'),
                                'navigation' => __('Tabs & pagination'),
                                'avatars' => __('Avatars & skeletons'),
                            ] as $id => $label)
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-2" href="#{{ $id }}">{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="col-12 col-xl-9">
                {{-- ------------------------------------------------------------
                     Tokens
                     ------------------------------------------------------------ --}}
                <section id="tokens" class="card edu-card mb-4" aria-labelledby="tokens-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="tokens-title">{{ __('Colour tokens') }}</h2>
                        <div class="small text-muted">
                            {{ __('Defined once in resources/sass/_variables.scss, consumed by every component in light and dark mode.') }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ([
                                ['--edu-primary', 'Primary'],
                                ['--edu-secondary', 'Secondary'],
                                ['--edu-success', 'Success'],
                                ['--edu-warning', 'Warning'],
                                ['--edu-danger', 'Danger'],
                                ['--edu-info', 'Info'],
                                ['--edu-surface', 'Surface'],
                                ['--edu-surface-soft', 'Surface soft'],
                                ['--edu-surface-muted', 'Surface muted'],
                                ['--edu-border', 'Border'],
                                ['--edu-text', 'Text'],
                                ['--edu-muted', 'Muted'],
                            ] as [$token, $label])
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="edu-swatch" style="background: var({{ $token }});" aria-hidden="true"></div>
                                    <div class="small fw-semibold mt-1">{{ $label }}</div>
                                    <code class="small text-muted">{{ $token }}</code>
                                </div>
                            @endforeach
                        </div>

                        <hr>

                        <h3 class="h6 fw-bold">{{ __('Spacing scale') }}</h3>
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            @foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $step)
                                <div class="text-center">
                                    <div class="bg-edu-soft border rounded" style="width: var(--edu-space-{{ $step }}); height: 28px;"></div>
                                    <div class="small text-muted mt-1">{{ $step }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-6">
                                <h3 class="h6 fw-bold">{{ __('Radius') }}</h3>
                                <div class="d-flex gap-2">
                                    @foreach (['xs' => '8px', '' => '16px', 'sm' => '12px', 'lg' => '20px'] as $name => $px)
                                        <div class="border bg-edu-soft d-flex align-items-end justify-content-center small text-muted p-1"
                                             style="width: 72px; height: 56px; border-radius: var(--edu-radius-{{ $name ?: 'base' }});">
                                            {{ $px }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <h3 class="h6 fw-bold">{{ __('Shadows') }}</h3>
                                <div class="d-flex gap-2">
                                    @foreach (['xs' => 'XS', 'card' => 'Card', 'hover' => 'Hover', 'pop' => 'Pop'] as $name => $label)
                                        <div class="bg-body rounded p-3 small" style="box-shadow: var(--edu-shadow-{{ $name }});">
                                            {{ $label }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Typography
                     ------------------------------------------------------------ --}}
                <section id="typography" class="card edu-card mb-4" aria-labelledby="type-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="type-title">{{ __('Typography') }}</h2>
                        <div class="small text-muted">{{ __('Inter with a system-font fallback, 1.5 line-height, 70ch reading width.') }}</div>
                    </div>
                    <div class="card-body edu-measure">
                        <div class="edu-eyebrow mb-2">{{ __('Eyebrow label') }}</div>
                        <p class="edu-landing-title mb-2">{{ __('Display headline') }}</p>
                        <h1>{{ __('Heading one') }}</h1>
                        <h2>{{ __('Heading two') }}</h2>
                        <h3>{{ __('Heading three') }}</h3>
                        <h4>{{ __('Heading four') }}</h4>
                        <h5>{{ __('Heading five') }}</h5>
                        <h6>{{ __('Heading six') }}</h6>
                        <p class="mb-2">
                            {{ __('Body copy stays comfortable at 15px with a 1.5 line-height. Links look like') }}
                            <a href="#tokens">{{ __('this') }}</a>,
                            <strong>{{ __('bold text') }}</strong>,
                            <em>{{ __('emphasis') }}</em>
                            {{ __('and') }} <code>{{ __('inline code') }}</code>.
                        </p>
                        <p class="small text-muted mb-0">{{ __('Small helper text, captions and timestamps.') }}</p>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Buttons
                     ------------------------------------------------------------ --}}
                <section id="buttons" class="card edu-card mb-4" aria-labelledby="buttons-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="buttons-title">{{ __('Buttons') }}</h2>
                        <div class="small text-muted">{{ __('One primary action per page; 44px minimum hit area on touch.') }}</div>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Primary') }}</button>
                            <button type="button" class="btn btn-secondary">{{ __('Secondary') }}</button>
                            <button type="button" class="btn btn-outline-primary">{{ __('Outline') }}</button>
                            <button type="button" class="btn btn-soft-primary">{{ __('Soft') }}</button>
                            <button type="button" class="btn btn-light border">{{ __('Light') }}</button>
                            <button type="button" class="btn btn-danger"><i class="bi bi-trash" aria-hidden="true"></i> {{ __('Danger') }}</button>
                            <button type="button" class="btn btn-link">{{ __('Ghost link') }}</button>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-primary" disabled>{{ __('Disabled') }}</button>
                            <button type="button" class="btn btn-primary is-loading" aria-busy="true">
                                <span>{{ __('Saving') }}</span>
                            </button>
                            <span class="small text-muted">{{ __('Loading state: spinner replaces the label and the button cannot be pressed twice.') }}</span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-primary">{{ __('Small') }}</button>
                            <button type="button" class="btn btn-primary">{{ __('Default') }}</button>
                            <button type="button" class="btn btn-lg btn-primary">{{ __('Large') }}</button>
                            <button type="button" class="btn edu-icon-btn" aria-label="{{ __('Icon only example') }}">
                                <i class="bi bi-heart" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Forms
                     ------------------------------------------------------------ --}}
                <section id="forms" class="card edu-card mb-4" aria-labelledby="forms-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="forms-title">{{ __('Forms') }}</h2>
                        <div class="small text-muted">{{ __('Labels are always visible; errors sit next to the field and never erase input.') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="guide-text" class="form-label">{{ __('Text input') }}</label>
                                <input id="guide-text" type="text" class="form-control" value="{{ __('Maria Rossi') }}">
                                <div class="form-text">{{ __('Helper text explains the expected format.') }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-select" class="form-label">{{ __('Select') }}</label>
                                <select id="guide-select" class="form-select">
                                    <option>{{ __('Student') }}</option>
                                    <option>{{ __('Teacher') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-valid" class="form-label">{{ __('Valid state') }}</label>
                                <input id="guide-valid" type="email" class="form-control is-valid" value="you@example.com">
                                <div class="valid-feedback">{{ __('Looks good.') }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-invalid" class="form-label">{{ __('Invalid state') }}</label>
                                <input id="guide-invalid" type="email" class="form-control is-invalid" value="not-an-email"
                                       aria-describedby="guide-invalid-error" aria-invalid="true">
                                <div class="invalid-feedback" id="guide-invalid-error">{{ __('Enter a valid email address.') }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-floating" class="form-label">{{ __('Floating label') }}</label>
                                <div class="form-floating">
                                    <input id="guide-floating" type="text" class="form-control" placeholder="{{ __('Subject') }}">
                                    <label for="guide-floating">{{ __('Subject') }}</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="guide-file" class="form-label">{{ __('File input') }}</label>
                                <input id="guide-file" type="file" class="form-control">
                                <div class="form-text">{{ __('JPG, PNG or WebP, up to 2 MB.') }}</div>
                            </div>
                            <div class="col-12">
                                <label for="guide-textarea" class="form-label">{{ __('Textarea with counter') }}</label>
                                <textarea id="guide-textarea" class="form-control" rows="2" maxlength="280"></textarea>
                                <div class="form-text text-end">0/280</div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input id="guide-check" type="checkbox" class="form-check-input" checked>
                                    <label for="guide-check" class="form-check-label">{{ __('Checkbox option') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Badges
                     ------------------------------------------------------------ --}}
                <section id="badges" class="card edu-card mb-4" aria-labelledby="badges-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="badges-title">{{ __('Badges') }}</h2>
                        <div class="small text-muted">{{ __('Roles, class states and report states share one palette.') }}</div>
                    </div>
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Roles') }}:</span>
                            <span class="badge text-bg-dark text-capitalize">{{ __('Admin') }}</span>
                            <span class="badge text-bg-primary text-capitalize">{{ __('Teacher') }}</span>
                            <span class="badge text-bg-success text-capitalize">{{ __('Student') }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Class') }}:</span>
                            <x-status-badge status="scheduled" />
                            <x-status-badge status="live" />
                            <x-status-badge status="completed" />
                            <x-status-badge status="hidden" />
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Report') }}:</span>
                            <x-status-badge status="pending" />
                            <x-status-badge status="reviewed" />
                            <x-status-badge status="dismissed" />
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-1">{{ __('Counters') }}:</span>
                            <span class="edu-chip"><i class="bi bi-people" aria-hidden="true"></i> 128</span>
                            <span class="edu-chip"><i class="bi bi-heart" aria-hidden="true"></i> 42</span>
                            <span class="badge rounded-pill text-bg-danger">9+</span>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Cards and empty states
                     ------------------------------------------------------------ --}}
                <section id="cards" class="card edu-card mb-4" aria-labelledby="cards-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="cards-title">{{ __('Cards & empty states') }}</h2>
                        <div class="small text-muted">{{ __('Cards lift by 2px on hover; empty states always offer a next step.') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="card edu-card edu-card-hover h-100">
                                    <div class="card-body">
                                        <div class="edu-eyebrow mb-2">{{ __('Standard card') }}</div>
                                        <h3 class="h6 fw-bold">{{ __('Algebra basics') }}</h3>
                                        <p class="small text-muted mb-3">{{ __('A short description keeps the card useful without becoming a wall of text.') }}</p>
                                        <a href="#cards" class="btn btn-sm btn-primary">{{ __('Enroll') }}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <x-empty-state icon="bi-easel"
                                               :title="__('No classes yet')"
                                               :message="__('Create your first class and it will show up here.')">
                                    <a href="#cards" class="btn btn-primary">{{ __('Create a class') }}</a>
                                </x-empty-state>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Tables
                     ------------------------------------------------------------ --}}
                <section id="tables" class="card edu-card mb-4" aria-labelledby="tables-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="tables-title">{{ __('Tables') }}</h2>
                        <div class="small text-muted">{{ __('Sticky headers on desktop, labelled cards below 992px.') }}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Person') }}</th>
                                    <th scope="col">{{ __('Role') }}</th>
                                    <th scope="col">{{ __('Status') }}</th>
                                    <th scope="col" class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="{{ __('Person') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="auth()->user()" size="sm" />
                                            <span class="fw-semibold">{{ auth()->user()->name }}</span>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}"><span class="badge text-bg-primary text-capitalize">{{ auth()->user()->role }}</span></td>
                                    <td data-label="{{ __('Status') }}"><span class="badge text-bg-success">{{ __('Active') }}</span></td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td data-label="{{ __('Person') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar-sm bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center fw-bold">EC</span>
                                            <span class="fw-semibold">{{ __('Example Student') }}</span>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}"><span class="badge text-bg-success text-capitalize">{{ __('student') }}</span></td>
                                    <td data-label="{{ __('Status') }}"><span class="badge text-bg-danger">{{ __('Blocked') }}</span></td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Feedback: modal, toast, dropdown
                     ------------------------------------------------------------ --}}
                <section id="feedback" class="card edu-card mb-4" aria-labelledby="feedback-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="feedback-title">{{ __('Modals, toasts & dropdowns') }}</h2>
                        <div class="small text-muted">{{ __('Destructive actions always ask first; toasts announce themselves to screen readers.') }}</div>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#guideModal">
                            {{ __('Open example modal') }}
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                {{ __('Dropdown') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#feedback"><i class="bi bi-pencil me-1" aria-hidden="true"></i>{{ __('Edit') }}</a></li>
                                <li><a class="dropdown-item" href="#feedback"><i class="bi bi-link me-1" aria-hidden="true"></i>{{ __('Copy link') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="dropdown-item text-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i>{{ __('Delete') }}</button></li>
                            </ul>
                        </div>

                        <div class="edu-toast-stack position-static" role="status" aria-live="polite">
                            <div class="edu-toast is-success">
                                <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                                <div class="small fw-semibold">{{ __('Saved successfully.') }}</div>
                            </div>
                        </div>

                        <div class="w-100">
                            <div class="alert alert-success mb-2" role="alert"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ __('Success alert') }}</div>
                            <div class="alert alert-warning mb-2" role="alert"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>{{ __('Warning alert') }}</div>
                            <div class="alert alert-danger mb-0" role="alert"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>{{ __('Error alert') }}</div>
                        </div>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Navigation: tabs, breadcrumbs, pagination
                     ------------------------------------------------------------ --}}
                <section id="navigation" class="card edu-card mb-4" aria-labelledby="nav-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="nav-title">{{ __('Tabs, breadcrumbs & pagination') }}</h2>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#guide-tab-one"
                                        type="button" role="tab" aria-selected="true">{{ __('Posts') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#guide-tab-two"
                                        type="button" role="tab" aria-selected="false">{{ __('Classes') }}</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="guide-tab-one" role="tabpanel">
                                <p class="small text-muted mb-0">{{ __('Tab one content.') }}</p>
                            </div>
                            <div class="tab-pane fade" id="guide-tab-two" role="tabpanel">
                                <p class="small text-muted mb-0">{{ __('Tab two content.') }}</p>
                            </div>
                        </div>

                        <hr>

                        <nav aria-label="{{ __('Example breadcrumb') }}">
                            <ol class="breadcrumb mb-3">
                                <li class="breadcrumb-item"><a href="#navigation">{{ __('Dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="#navigation">{{ __('Classes') }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ __('Algebra basics') }}</li>
                            </ol>
                        </nav>

                        <nav aria-label="{{ __('Example pagination') }}">
                            <ul class="pagination mb-0">
                                <li class="page-item disabled"><span class="page-link">{{ __('Previous') }}</span></li>
                                <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                                <li class="page-item"><a class="page-link" href="#navigation">2</a></li>
                                <li class="page-item"><a class="page-link" href="#navigation">{{ __('Next') }}</a></li>
                            </ul>
                        </nav>
                    </div>
                </section>

                {{-- ------------------------------------------------------------
                     Avatars, progress and skeletons
                     ------------------------------------------------------------ --}}
                <section id="avatars" class="card edu-card mb-4" aria-labelledby="avatars-title">
                    <div class="card-header">
                        <h2 class="h6 fw-bold mb-0" id="avatars-title">{{ __('Avatars, progress & skeletons') }}</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-end gap-3 mb-3">
                            <x-avatar :user="auth()->user()" size="sm" />
                            <x-avatar :user="auth()->user()" size="md" />
                            <x-avatar :user="auth()->user()" size="lg" />
                            <x-avatar :user="auth()->user()" size="xl" />
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="progress" role="progressbar" aria-label="{{ __('Upload progress') }}"
                                     aria-valuenow="65" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
                                    <div class="progress-bar" style="width: 65%"></div>
                                </div>
                                <div class="small text-muted mt-1">65%</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="edu-skeleton" style="height: 14px; width: 80%;"></div>
                                <div class="edu-skeleton mt-2" style="height: 14px; width: 55%;"></div>
                                <div class="edu-skeleton mt-2" style="height: 14px; width: 68%;"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <p class="small text-muted">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                    {{ __('Every example above uses the same tokens and works in light and dark mode. Toggle the theme with the switch in the top bar.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Example modal, kept at the end so it never affects the layout. --}}
    <div class="modal fade" id="guideModal" tabindex="-1" aria-labelledby="guideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="guideModalLabel">{{ __('Example modal') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Use a modal for a short confirmation or a focused task. The close button is always reachable with the keyboard.') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary">{{ __('Confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
