<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SalesTarget;
use App\Http\Requests\StoreSalesTargetRequest;
use App\Http\Requests\UpdateSalesTargetRequest;

class SalesTargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = SalesTarget::query();

        $query->when(request('year'), fn ($q) => $q->where('year', request('year')));
        $query->when(request('month'), fn ($q) => $q->where('month', request('month')));

        $perPage = (int) request('per_page', 12);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 12;

        $targets = $query->orderByDesc('year')->orderByDesc('month')->paginate($perPage)->withQueryString();

        return view('admin.salestargets.index', compact('targets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.salestargets.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalesTargetRequest $request)
    {
        SalesTarget::query()->create($request->validated());

        return redirect()
            ->route('admin.salestargets.index')
            ->with('success', 'Sales target created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesTarget $salesTarget)
    {
        return redirect()->route('admin.salestargets.edit', $salesTarget);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesTarget $salesTarget)
    {
        return view('admin.salestargets.edit', compact('salesTarget'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesTargetRequest $request, SalesTarget $salesTarget)
    {
        $salesTarget->update($request->validated());

        return redirect()
            ->route('admin.salestargets.index')
            ->with('success', 'Sales target updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesTarget $salesTarget)
    {
        $salesTarget->delete();

        return redirect()
            ->route('admin.salestargets.index')
            ->with('success', 'Sales target deleted successfully.');
    }
}
