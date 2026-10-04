<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'nbUtilisateurs' => User::where('role', User::ROLE_USER)->count(),
            'nbAdmins' => User::where('role', User::ROLE_ADMIN)->count(),
            'derniersInscrits' => User::latest()->take(5)->get(),
        ]);
    }
}
