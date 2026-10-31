<x-app-layout>
    <x-slot name="title">{{ __('Users') }}</x-slot>

    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Admin') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Users') }}</li>
    </x-slot>

    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h5 fw-semibold mb-0">
                    <i class="bi bi-people me-1 text-edu" aria-hidden="true"></i> {{ __('Users') }}
                </h1>
                <p class="small text-muted mb-0">{{ __('Search, block, unblock and delete accounts (FR-27).') }}</p>
            </div>
            <span class="edu-chip">
                <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                {{ $users->total() }} {{ __('accounts') }}
            </span>
        </div>
    </x-slot>

    <div class="container-xxl px-3 px-lg-4">
        <section class="card edu-card mb-3" aria-labelledby="user-filters-title">
            <div class="card-body">
                <h2 class="visually-hidden" id="user-filters-title">{{ __('Filter users') }}</h2>

                <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="user-q" class="form-label small">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" id="user-q" value="{{ $term }}" class="form-control"
                                   placeholder="{{ __('Name or email') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="user-role" class="form-label small">{{ __('Role') }}</label>
                        <select name="role" id="user-role" class="form-select">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach ([
                                \App\Models\User::ROLE_ADMIN => __('Admin'),
                                \App\Models\User::ROLE_TEACHER => __('Teacher'),
                                \App\Models\User::ROLE_STUDENT => __('Student'),
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="user-blocked" class="form-label small">{{ __('Status') }}</label>
                        <select name="blocked" id="user-blocked" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="0" @selected($blocked === '0')>{{ __('Active') }}</option>
                            <option value="1" @selected($blocked === '1')>{{ __('Blocked') }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-funnel me-1" aria-hidden="true"></i> {{ __('Filter') }}
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>

                {{-- One-tap role chips, keeping the search term. --}}
                <div class="d-flex flex-wrap gap-2 mt-3" aria-label="{{ __('Filter by role') }}">
                    <a class="edu-filter-chip {{ $role === '' ? 'is-active' : '' }}"
                       href="{{ route('admin.users.index', array_filter(['q' => $term, 'blocked' => $blocked])) }}">
                        {{ __('All roles') }}
                    </a>
                    @foreach ([
                        \App\Models\User::ROLE_ADMIN => __('Admin'),
                        \App\Models\User::ROLE_TEACHER => __('Teacher'),
                        \App\Models\User::ROLE_STUDENT => __('Student'),
                    ] as $value => $label)
                        <a class="edu-filter-chip {{ $role === $value ? 'is-active' : '' }}"
                           href="{{ route('admin.users.index', array_filter(['q' => $term, 'role' => $value, 'blocked' => $blocked])) }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($users->isEmpty())
            <x-empty-state icon="bi-person-x"
                           :title="__('No users match these filters.')"
                           :message="__('Try a different name, role or status, or reset the filters.')">
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary">{{ __('Reset the filters') }}</a>
            </x-empty-state>
        @else
            <div class="card edu-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 edu-table-sticky edu-table-cards">
                        <caption class="visually-hidden">{{ __('All accounts, newest first') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('User') }}</th>
                                <th scope="col">{{ __('Role') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col" class="text-end">{{ __('Posts') }}</th>
                                <th scope="col" class="text-end">{{ __('Classes') }}</th>
                                <th scope="col">{{ __('Joined') }}</th>
                                <th scope="col" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $roleColor = match ($user->role) {
                                        \App\Models\User::ROLE_ADMIN => 'dark',
                                        \App\Models\User::ROLE_TEACHER => 'primary',
                                        default => 'success',
                                    };
                                    $protected = auth()->id() === $user->id || $user->isAdmin();
                                @endphp
                                <tr>
                                    <td data-label="{{ __('User') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-avatar :user="$user" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('users.show', $user) }}"
                                                   class="fw-semibold text-decoration-none text-reset text-break">{{ $user->name }}</a>
                                                <div class="small text-muted text-break">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="{{ __('Role') }}">
                                        <span class="badge text-bg-{{ $roleColor }} text-capitalize">{{ $user->role }}</span>
                                    </td>
                                    <td data-label="{{ __('Status') }}">
                                        @if ($user->is_blocked)
                                            <span class="badge text-bg-danger">{{ __('Blocked') }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ __('Active') }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('Posts') }}" class="text-end">{{ $user->posts_count }}</td>
                                    <td data-label="{{ __('Classes') }}" class="text-end">{{ $user->classes_count }}</td>
                                    <td data-label="{{ __('Joined') }}" class="text-nowrap small text-muted">
                                        <time datetime="{{ $user->created_at?->toIso8601String() }}">{{ $user->created_at?->format('d M Y') }}</time>
                                    </td>
                                    <td data-label="{{ __('Actions') }}" class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            @if ($user->is_blocked)
                                                @can('unblock', $user)
                                                    <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-person-check me-1" aria-hidden="true"></i> {{ __('Unblock') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('block', $user)
                                                    {{-- Opens the Bootstrap confirmation modal rendered under the table. --}}
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                            data-bs-toggle="modal" data-bs-target="#block-user-{{ $user->id }}"
                                                            aria-label="{{ __('Block :name', ['name' => $user->name]) }}">
                                                        <i class="bi bi-person-slash me-1" aria-hidden="true"></i> {{ __('Block') }}
                                                    </button>
                                                @endcan
                                            @endif

                                            @can('delete', $user)
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal" data-bs-target="#delete-user-{{ $user->id }}"
                                                        title="{{ __('Delete') }}" aria-label="{{ __('Delete :name', ['name' => $user->name]) }}">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            @endcan

                                            @if ($protected)
                                                <span class="text-muted small align-self-center">{{ __('protected') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="card-body pt-0">
                        <x-pagination-links :paginator="$users" />
                    </div>
                @endif
            </div>

            {{-- Confirmations live outside the table so the markup stays valid. --}}
            @foreach ($users as $user)
                @can('block', $user)
                    <x-confirm-delete-modal
                        :id="'block-user-'.$user->id"
                        :action="route('admin.users.block', $user)"
                        method="PATCH"
                        :title="__('Block :name?', ['name' => $user->name])"
                        :message="__('They will be logged out immediately and cannot sign in until you unblock them.')"
                        :confirm-label="__('Block account')"
                        confirm-class="btn btn-warning"
                        :show-trigger="false" />
                @endcan

                @can('delete', $user)
                    <x-confirm-delete-modal
                        :id="'delete-user-'.$user->id"
                        :action="route('admin.users.destroy', $user)"
                        :title="__('Delete :name?', ['name' => $user->name])"
                        :message="__('This deletes the account, their posts, comments and reports. This cannot be undone.')"
                        :confirm-label="__('Delete account')"
                        :show-trigger="false" />
                @endcan
            @endforeach
        @endif
    </div>
</x-app-layout>
