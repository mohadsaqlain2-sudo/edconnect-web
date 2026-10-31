@include('errors.page', [
    'code' => '419',
    'icon' => 'bi-hourglass-split',
    'title' => __('Your session expired'),
    'message' => __('For your safety the page was idle for too long. Refresh the page, then send the form again. Nothing was lost except the last click.'),
])
