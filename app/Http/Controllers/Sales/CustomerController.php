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
        $query = Customer::query()->withTrashed()->with('creator');
        $countries = $this->countryOptions();

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('company_name', 'like', "%{$term}%")
                ->orWhere('country', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });

        $query->when(request('customer_type'), fn ($q) => $q->where('customer_type', request('customer_type')));
        $query->when(request('created_by'), fn ($q) => $q->where('created_by', request('created_by')));
        $query->when(request('country'), fn ($q) => $q->where('country', 'like', '%' . request('country') . '%'));

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $customers = $query->latest()->paginate($perPage)->withQueryString();
        $actor = auth()->user();
        $creators = \App\Models\User::query()
            ->when(! $actor?->isSuperAdmin(), fn ($query) => $query->withoutSuperAdmin())
            ->orderBy('name')
            ->get();

        $totalCustomers = Customer::withTrashed()->count();
        $activeCustomers = Customer::query()->count();
        $inactiveCustomers = Customer::onlyTrashed()->count();
        $companyCustomers = Customer::withTrashed()->where('customer_type', 'company')->count();
        $individualCustomers = Customer::withTrashed()->where('customer_type', 'individual')->count();
        $newThisMonth = Customer::withTrashed()
            ->whereDate('created_at', '>=', now()->startOfMonth())
            ->count();
        $countryCount = Customer::query()->whereNotNull('country')->distinct('country')->count('country');
        $topCountries = Customer::query()
            ->selectRaw('country, COUNT(*) as total')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $topCountry = $topCountries->first();

        $statsCards = [
            [
                'key' => 'total',
                'label' => ui_phrase('Total'),
                'value' => $totalCustomers,
                'caption' => ui_phrase('All customers'),
                'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
            ],
            [
                'key' => 'active',
                'label' => ui_phrase('Active'),
                'value' => $activeCustomers,
                'caption' => ui_phrase('Currently active'),
                'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ],
            [
                'key' => 'inactive',
                'label' => ui_phrase('Inactive'),
                'value' => $inactiveCustomers,
                'caption' => ui_phrase('Deactivated'),
                'tone' => 'bg-rose-50 text-rose-700 border-rose-100',
            ],
            [
                'key' => 'company',
                'label' => ui_phrase('company'),
                'value' => $companyCustomers,
                'caption' => ui_phrase('Company type'),
                'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            ],
            [
                'key' => 'individual',
                'label' => ui_phrase('individual'),
                'value' => $individualCustomers,
                'caption' => ui_phrase('Individual type'),
                'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
            ],
            [
                'key' => 'countries',
                'label' => ui_phrase('countries'),
                'value' => $countryCount,
                'caption' => ui_phrase('Distinct countries'),
                'tone' => 'bg-teal-50 text-teal-700 border-teal-100',
            ],
        ];

        return view('modules.customers.index', compact(
            'customers',
            'creators',
            'countries',
            'statsCards',
            'countryCount',
            'topCountries'
        ));
    }

    public function create()
    {
        $countries = $this->countryOptions();
        return view('modules.customers.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'country' => ['required', 'string', Rule::in(array_values($this->countryOptions()))],
            'customer_type' => ['required', Rule::in(['individual', 'company'])],
            'company_name' => ['nullable', 'required_if:customer_type,company', 'string', 'max:255'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));
        $validated['created_by'] = auth()->id();

        Customer::query()->create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', ui_phrase('Customer created successfully.'));
    }

    public function edit(Customer $customer)
    {
        $countries = $this->countryOptions();
        return view('modules.customers.edit', compact('customer', 'countries'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($customer->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'country' => ['required', 'string', Rule::in(array_values($this->countryOptions()))],
            'customer_type' => ['required', Rule::in(['individual', 'company'])],
            'company_name' => ['nullable', 'required_if:customer_type,company', 'string', 'max:255'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));
        $customer->update($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', ui_phrase('Customer updated successfully.'));
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', ui_phrase('Customer deactivated successfully.'));
    }

    public function toggleStatus($customer)
    {
        $customer = Customer::withTrashed()->findOrFail($customer);
        if ($customer->trashed()) {
            $customer->restore();

            return redirect()
                ->route('customers.index')
                ->with('success', ui_phrase('Customer activated successfully.'));
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', ui_phrase('Customer deactivated successfully.'));
    }

    public function checkCode(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        $ignoreId = (int) $request->query('ignore_id', 0);

        if ($code === '') {
            return response()->json([
                'available' => false,
                'message' => ui_phrase('Code is required.'),
            ]);
        }

        $exists = Customer::query()
            ->where('code', $code)
            ->when($ignoreId > 0, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? ui_phrase('Code is already in use.') : ui_phrase('Code is available.'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function countryOptions(): array
    {
        $countries = config('countries', []);
        asort($countries);
        return $countries;
    }
}
