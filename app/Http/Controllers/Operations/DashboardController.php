<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('operations.dashboard');
    }
}
