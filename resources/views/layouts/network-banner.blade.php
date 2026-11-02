{{-- Phase 3F: one banner for offline, Save-Data and slow connections.
     resources/js/adaptive.js shows/hides it and fills the message. --}}
<div class="edu-network-banner" id="eduNetworkBanner" role="status" aria-live="polite" hidden
     data-offline-text="{{ __('You are offline. Pages already loaded still work; new pages will load when the connection returns.') }}"
     data-slow-text="{{ __('Slow connection or Save-Data is on. Images and background updates are reduced to save data.') }}"
     data-online-text="{{ __('You are back online.') }}">
    <i class="bi bi-wifi-off" aria-hidden="true"></i>
    <span data-network-message>{{ __('Slow connection or Save-Data is on.') }}</span>
    <button type="button" class="btn-close ms-auto" data-network-dismiss
            aria-label="{{ __('Dismiss') }}"></button>
</div>
