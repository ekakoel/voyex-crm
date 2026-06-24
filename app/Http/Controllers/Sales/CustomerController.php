<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPageOptions = [10, 25, 50, 100];
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', Rule::in(['individual', 'company'])],
            'per_page' => ['nullable', Rule::in(array_map('strval', $perPageOptions))],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $customerType = (string) ($validated['customer_type'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 10);
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 10;

        $filteredQuery = $this->applyIndexFilters(
            Customer::query()->withTrashed(),
            $search,
            $customerType
        );

        $customers = (clone $filteredQuery)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $currentUser = auth()->user();
        $canManageActivationActions = $currentUser instanceof \App\Models\User && $currentUser->canManageActivationActions();
        $customerRows = $this->buildCustomerIndexRows($customers, $canManageActivationActions);
        $summaries = $this->buildCustomerSummaries($filteredQuery);
        $sidebarInfo = $this->buildCustomerSidebarInfo($filteredQuery);

        $statsCards = [
            [
                'key' => 'total',
                'label' => ui_phrase('Filtered Customers'),
                'value' => $summaries['total'],
                'caption' => ui_phrase('Current filter result'),
                'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
            ],
            [
                'key' => 'active',
                'label' => ui_phrase('Active'),
                'value' => $summaries['active'],
                'caption' => ui_phrase('Active in current result'),
                'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ],
            [
                'key' => 'inactive',
                'label' => ui_phrase('Inactive'),
                'value' => $summaries['inactive'],
                'caption' => ui_phrase('Inactive in current result'),
                'tone' => 'bg-rose-50 text-rose-700 border-rose-100',
            ],
            [
                'key' => 'company',
                'label' => ui_phrase('type company'),
                'value' => $summaries['company'],
                'caption' => ui_phrase('Company in current result'),
                'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            ],
            [
                'key' => 'individual',
                'label' => ui_phrase('type individual'),
                'value' => $summaries['individual'],
                'caption' => ui_phrase('Individual in current result'),
                'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
            ],
            [
                'key' => 'countries',
                'label' => ui_phrase('countries'),
                'value' => $summaries['countries'],
                'caption' => ui_phrase('Countries in current result'),
                'tone' => 'bg-teal-50 text-teal-700 border-teal-100',
            ],
        ];

        return view('modules.customers.index', compact(
            'customers',
            'statsCards',
            'sidebarInfo',
            'customerRows',
            'perPageOptions',
            'canManageActivationActions'
        ));
    }

    private function applyIndexFilters($query, string $search, string $customerType)
    {
        return $query
            ->when($search !== '', function ($query) use ($search) {
                if (mb_strlen($search) < 3) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($customerType !== '', fn ($query) => $query->where('customer_type', $customerType));
    }

    private function buildCustomerSummaries($query): array
    {
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as active_total')
            ->selectRaw('SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as inactive_total')
            ->selectRaw("SUM(CASE WHEN customer_type = 'company' THEN 1 ELSE 0 END) as company_total")
            ->selectRaw("SUM(CASE WHEN customer_type = 'individual' THEN 1 ELSE 0 END) as individual_total")
            ->first();

        $countryCount = (clone $query)
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct('country')
            ->count('country');

        return [
            'total' => (int) ($summary->total ?? 0),
            'active' => (int) ($summary->active_total ?? 0),
            'inactive' => (int) ($summary->inactive_total ?? 0),
            'company' => (int) ($summary->company_total ?? 0),
            'individual' => (int) ($summary->individual_total ?? 0),
            'countries' => (int) $countryCount,
        ];
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
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
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

    private function buildCustomerIndexRows($customers, bool $canManageActivationActions): array
    {
        return $customers->getCollection()->values()->map(function (Customer $customer, int $index) use ($customers, $canManageActivationActions): array {
            $isActive = ! $customer->trashed();
            $customerType = (string) $customer->customer_type;

            return [
                'customer' => $customer,
                'row_number' => (int) $customers->firstItem() + $index,
                'is_active' => $isActive,
                'code' => (string) $customer->code,
                'name' => (string) $customer->name,
                'email' => $customer->email ?: '-',
                'phone' => $customer->phone ?: '-',
                'country' => $customer->country ?: '-',
                'company_name' => $customer->company_name ?: '-',
                'customer_type_label' => $customerType !== ''
                    ? ui_phrase('type ' . $customerType)
                    : ui_phrase('unknown'),
                'edit_url' => route('customers.edit', $customer),
                'toggle_url' => route('customers.toggle-status', $customer->id),
                'toggle_modal_title' => $isActive
                    ? ui_phrase('Deactivate') . ' ' . ui_phrase('Customer')
                    : ui_phrase('Activate') . ' ' . ui_phrase('Customer'),
                'toggle_message' => $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate'),
                'toggle_label' => $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate'),
                'toggle_icon' => $isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4',
                'toggle_class' => $isActive
                    ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20'
                    : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20',
                'can_manage_activation' => $canManageActivationActions,
            ];
        })->all();
    }

    private function buildCustomerSidebarInfo($summaryQuery): array
    {
        $totalFiltered = (clone $summaryQuery)->count();
        $activeFiltered = (clone $summaryQuery)->whereNull('deleted_at')->count();
        $inactiveFiltered = (clone $summaryQuery)->whereNotNull('deleted_at')->count();
        $typeDistributionFiltered = (clone $summaryQuery)
            ->selectRaw('COALESCE(NULLIF(customer_type, \'\'), \'unknown\') AS customer_type, COUNT(*) as total')
            ->groupBy('customer_type')
            ->pluck('total', 'customer_type');
        $topCountryFiltered = (clone $summaryQuery)
            ->selectRaw('country, COUNT(*) as total')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('total')
            ->value('country');

        return [
            'title' => ui_phrase('Customer/Agent Info'),
            'subtitle' => ui_phrase('Summary of current customer/agent list.'),
            'rows' => [
                ['label' => ui_phrase('Total Customers'), 'value' => $totalFiltered, 'valueClass' => 'text-gray-800 dark:text-gray-100'],
                ['label' => ui_phrase('Active'), 'value' => $activeFiltered, 'valueClass' => 'text-emerald-700 dark:text-emerald-300'],
                ['label' => ui_phrase('Inactive'), 'value' => $inactiveFiltered, 'valueClass' => 'text-rose-700 dark:text-rose-300'],
                [
                    'label' => ui_phrase('Type Distribution'),
                    'value' => collect($typeDistributionFiltered)
                        ->map(function ($count, $type): string {
                            $normalizedType = (string) $type;

                            return ($normalizedType !== 'unknown'
                                ? ui_phrase('type ' . $normalizedType)
                                : ui_phrase('unknown')) . ': ' . $count;
                        })
                        ->implode(', '),
                ],
                ['label' => ui_phrase('Top Country'), 'value' => $topCountryFiltered ?: '-'],
            ],
        ];
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
