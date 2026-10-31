@include('errors.page', [
    'code' => '403',
    'icon' => 'bi-shield-lock',
    'title' => __('You are not allowed to open this page'),
    'message' => __('Your account does not have the rights for this area. If you think this is a mistake, please contact an administrator.'),
])
