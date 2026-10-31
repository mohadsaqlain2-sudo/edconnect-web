@include('errors.page', [
    'code' => '404',
    'icon' => 'bi-compass',
    'title' => __('Page not found'),
    'message' => __('The page you are looking for has been moved or never existed. Check the address and try again.'),
])
