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
        $user = auth()->user();
        $canInvoices = (bool) $user?->can('module.invoices.access');
        $now = Carbon::now();

        $monthlyTotal = $canInvoices
            ? Invoice::query()
                ->whereMonth('invoice_date', $now->month)
                ->whereYear('invoice_date', $now->year)
                ->sum('total_amount')
            : 0;

        $pendingInvoices = $canInvoices
            ? Invoice::query()
                ->where('status', 'pending')
                ->count()
            : 0;

        $overdueInvoices = $canInvoices
            ? Invoice::query()
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $now->toDateString())
                ->whereNotIn('status', ['final', 'approved'])
                ->count()
            : 0;

        $recentInvoices = $canInvoices
            ? Invoice::query()
                ->latest()
                ->limit(6)
                ->get(['id', 'invoice_number', 'status', 'total_amount'])
            : collect();

        return view('finance.dashboard', compact(
            'monthlyTotal',
            'pendingInvoices',
            'overdueInvoices',
            'canInvoices',
            'recentInvoices'
        ));
    }
}
