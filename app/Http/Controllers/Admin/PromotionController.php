<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::query()->orderByDesc('id')->paginate(10);
        return view('admin.promotions.index', compact('promotions'));
    }

    public function create()
    {
        return view('admin.promotions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:promotions,code'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        Promotion::query()->create($validated);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion created successfully.');
    }

    public function edit(Promotion $promotion)
    {
        return view('admin.promotions.edit', compact('promotion'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotion->id)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $promotion->update($validated);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion updated successfully.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return redirect()->route('admin.promotions.index')->with('success', 'Promotion deleted successfully.');
    }
}
