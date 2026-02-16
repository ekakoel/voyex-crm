<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationTemplate;
use Illuminate\Http\Request;

class QuotationTemplateController extends Controller
{
    public function index()
    {
        $templates = QuotationTemplate::query()->orderBy('name')->paginate(10);
        return view('admin.quotation-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.quotation-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        QuotationTemplate::query()->create($validated);

        return redirect()->route('admin.quotation-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(QuotationTemplate $quotationTemplate)
    {
        return view('admin.quotation-templates.edit', compact('quotationTemplate'));
    }

    public function update(Request $request, QuotationTemplate $quotationTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $quotationTemplate->update($validated);

        return redirect()->route('admin.quotation-templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(QuotationTemplate $quotationTemplate)
    {
        $quotationTemplate->delete();
        return redirect()->route('admin.quotation-templates.index')->with('success', 'Template deleted successfully.');
    }
}
