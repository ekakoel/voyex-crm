<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $query = Customer::query()->with('creator');

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('company_name', 'like', "%{$term}%");
        });

        $query->when(request('customer_type'), fn ($q) => $q->where('customer_type', request('customer_type')));
        $query->when(request('created_by'), fn ($q) => $q->where('created_by', request('created_by')));

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $customers = $query->latest()->paginate($perPage)->withQueryString();
        $creators = \App\Models\User::query()->orderBy('name')->get();

        return view('sales.customers.index', compact('customers', 'creators'));
    }

    public function create()
    {
        return view('sales.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['required', Rule::in(['individual', 'company'])],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['created_by'] = auth()->id();

        Customer::query()->create($validated);

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return view('sales.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['required', Rule::in(['individual', 'company'])],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update($validated);

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
