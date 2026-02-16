<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('finance.dashboard');
    }
}
