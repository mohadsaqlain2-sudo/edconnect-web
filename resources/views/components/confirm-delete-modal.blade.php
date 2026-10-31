{{-- Reusable delete confirmation modal.
     Usage:
       <x-confirm-delete-modal
           id="delete-class-{{ $classRoom->id }}"
           :action="route('classes.destroy', $classRoom)"
           title="Delete this class?"
           message="The enrollments are removed as well."
       />

     The trigger button and the Bootstrap modal are rendered together, so the
     component works without any extra JavaScript. Pass :show-trigger="false"
     when you only need the modal markup. --}}
@props([
    'id',
    'action',
    'method' => 'DELETE',
    'title' => 'Delete this item?',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'confirmClass' => 'btn btn-danger',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'btn btn-sm btn-outline-danger',
    'triggerIcon' => 'bi-trash',
    'showTrigger' => true,
])

@if ($showTrigger)
    <button type="button" class="{{ $triggerClass }}" data-bs-toggle="modal" data-bs-target="#{{ $id }}">
        <i class="bi {{ $triggerIcon }} me-1" aria-hidden="true"></i>{{ $triggerLabel }}
    </button>
@endif

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $id }}-title">{{ $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">{{ $message }}</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method($method)
                    <button type="submit" class="{{ $confirmClass }}">
                        {{ $confirmLabel }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
