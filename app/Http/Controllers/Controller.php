<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Gives every controller $this->authorize(...) so policies like
     * PostPolicy and CommentPolicy can be enforced inside the actions.
     * Laravel 11/12 no longer adds this trait automatically.
     */
    use AuthorizesRequests;
}
