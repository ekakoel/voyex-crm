<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $monthlyTotal = Invoice::query()
            ->whereMonth('invoice_date', $now->month)
            ->whereYear('invoice_date', $now->year)
            ->sum('total_amount');

        $pendingInvoices = Invoice::query()
            ->where('status', 'pending')
            ->count();

        $overdueInvoices = Invoice::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $now->toDateString())
            ->whereNotIn('status', ['final', 'approved'])
            ->count();

        $recentInvoices = Invoice::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'invoice_number', 'status', 'total_amount']);

        return view('finance.dashboard', compact(
            'monthlyTotal',
            'pendingInvoices',
            'overdueInvoices',
            'recentInvoices'
        ));
    }
}
