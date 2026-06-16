<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminUsersController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'userName' => auth()->user()->name,
            'userRole' => auth()->user()->getRoleNames()->first(),
        ]);
    }
}
