@include('errors.page', [
    'code' => '500',
    'icon' => 'bi-tools',
    'title' => __('Something went wrong on our side'),
    'message' => __('The page could not be loaded. Your data is safe. Try again in a moment, or head back home and start from there.'),
])
