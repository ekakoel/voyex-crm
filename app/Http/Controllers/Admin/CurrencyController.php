<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Support\Currency as CurrencySupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $query = Currency::query();

        $search = trim((string) $request->get('q'));
        if (mb_strlen($search) >= 3) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }
        $query->when(($validated['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true));
        $query->when(($validated['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false));

        $perPage = (int) $request->input('per_page', 10);
        $perPageOptions = [10, 25, 50, 100];
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 10;
        $currencies = $query->orderByDesc('is_default')->orderBy('code')->paginate($perPage)->withQueryString();
        $bulkCurrencies = Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->take(10)
            ->get();
        $currencyRows = $this->buildCurrencyIndexRows($currencies);
        $bulkCurrencyRows = $this->buildCurrencyBulkRows($bulkCurrencies);
        $statusFilterOptions = [
            ['value' => 'active', 'label' => ui_phrase('Active')],
            ['value' => 'inactive', 'label' => ui_phrase('Inactive')],
        ];

        return view('modules.currencies.index', compact(
            'currencies',
            'currencyRows',
            'bulkCurrencyRows',
            'perPageOptions',
            'statusFilterOptions'
        ));
    }

    public function create(): View
    {
        return view('modules.currencies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        if (($validated['rate_to_idr'] ?? 0) <= 0) {
            return back()->withErrors(['rate_to_idr' => ui_phrase('Rate must be greater than 0.')])->withInput();
        }

        $currency = Currency::create($validated);

        if ($currency->is_default) {
            Currency::query()->where('id', '!=', $currency->id)->update(['is_default' => false]);
        }

        CurrencySupport::flushCache();

        return redirect()->route('currencies.index')->with('success', ui_phrase('Currency created.'));
    }

    public function edit(Currency $currency): View
    {
        $rateHistories = $currency->rateHistories()
            ->with('changer:id,name')
            ->latest('changed_at')
            ->take(20)
            ->get();

        return view('modules.currencies.edit', compact('currency', 'rateHistories'));
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $oldRate = (float) $currency->rate_to_idr;
        $validated = $this->validatePayload($request, $currency->id);

        if (($validated['rate_to_idr'] ?? 0) <= 0) {
            return back()->withErrors(['rate_to_idr' => ui_phrase('Rate must be greater than 0.')])->withInput();
        }

        $newRate = (float) ($validated['rate_to_idr'] ?? 0);
        if ($oldRate !== $newRate) {
            $this->authorizeRateUpdate();
        }

        $currency->update($validated);

        $newRate = (float) $currency->rate_to_idr;
        if ($oldRate !== $newRate) {
            $currency->rateHistories()->create([
                'old_rate_to_idr' => $oldRate,
                'new_rate_to_idr' => $newRate,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);
        }

        if ($currency->is_default) {
            Currency::query()->where('id', '!=', $currency->id)->update(['is_default' => false]);
        }

        CurrencySupport::flushCache();

        return redirect()->route('currencies.index')->with('success', ui_phrase('Currency updated.'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $this->authorizeRateUpdate();

        $validated = $request->validate([
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.id' => ['required', 'integer', 'exists:currencies,id'],
            'rates.*.rate_to_idr' => ['required', 'numeric', 'min:0.000001'],
            'rates.*.decimal_places' => ['nullable', 'integer', 'min:0', 'max:6'],
        ]);

        $ratePayloads = collect($validated['rates'] ?? []);
        $currencyIds = $ratePayloads->pluck('id')->unique()->values()->all();
        $currencies = Currency::query()->whereIn('id', $currencyIds)->get()->keyBy('id');

        foreach ($ratePayloads as $payload) {
            $currency = $currencies->get((int) $payload['id']);
            if (! $currency) {
                continue;
            }

            $oldRate = (float) $currency->rate_to_idr;
            $newRate = (float) $payload['rate_to_idr'];
            $decimalPlaces = array_key_exists('decimal_places', $payload) && $payload['decimal_places'] !== null
                ? (int) $payload['decimal_places']
                : $currency->decimal_places;
            if (strtoupper((string) ($currency->code ?? '')) === 'USD') {
                $decimalPlaces = 0;
            }

            if ($oldRate === $newRate && $decimalPlaces === (int) $currency->decimal_places) {
                continue;
            }

            $currency->update([
                'rate_to_idr' => $newRate,
                'decimal_places' => $decimalPlaces,
            ]);

            if ($oldRate !== $newRate) {
                $currency->rateHistories()->create([
                    'old_rate_to_idr' => $oldRate,
                    'new_rate_to_idr' => $newRate,
                    'changed_by' => auth()->id(),
                    'changed_at' => now(),
                ]);
            }
        }

        CurrencySupport::flushCache();

        return back()->with('success', ui_phrase('Bulk rate update saved.'));
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->withErrors(['currency' => ui_phrase('Default currency cannot be deleted.')]);
        }
        $activeCount = Currency::query()->where('is_active', true)->count();
        if ($currency->is_active && $activeCount <= 1) {
            return back()->withErrors(['currency' => ui_phrase('At least one active currency is required.')]);
        }

        $currency->delete();
        CurrencySupport::flushCache();

        return back()->with('success', ui_phrase('Currency deleted.'));
    }

    private function validatePayload(Request $request, ?int $currencyId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('currencies', 'code')->ignore($currencyId)],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'rate_to_idr' => ['required', 'numeric'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:6'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim((string) $validated['code']));
        if ($validated['code'] === 'USD') {
            $validated['decimal_places'] = 0;
        }
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);
        if ($validated['is_default']) {
            $validated['is_active'] = true;
        }

        return $validated;
    }

    private function authorizeRateUpdate(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('module.currencies.update')) {
            abort(403, ui_phrase('You do not have permission to update currency rates.'));
        }
    }

    private function buildCurrencyIndexRows($currencies): array
    {
        $firstItem = (int) ($currencies->firstItem() ?? 1);
        $canDelete = auth()->user()?->can('module.currencies.delete') === true;

        return $currencies->getCollection()->values()->map(function (Currency $currency, int $index) use ($firstItem, $canDelete): array {
            $decimalPlaces = (int) ($currency->decimal_places ?? 0);

            return [
                'currency' => $currency,
                'row_number' => $firstItem + $index,
                'code' => (string) ($currency->code ?? '-'),
                'name' => (string) ($currency->name ?? '-'),
                'symbol_label' => trim((string) ($currency->symbol ?? '')) ?: '-',
                'formatted_rate_to_idr' => number_format((float) ($currency->rate_to_idr ?? 0), $decimalPlaces, '.', ','),
                'decimal_places' => $decimalPlaces,
                'is_default' => (bool) ($currency->is_default ?? false),
                'status_badge' => $currency->is_active ? 'active' : 'inactive',
                'edit_url' => route('currencies.edit', $currency),
                'delete_url' => route('currencies.destroy', $currency),
                'can_delete' => $canDelete,
                'delete_modal_name_desktop' => 'currencies-index-delete-desktop-' . $currency->id,
                'delete_modal_name_mobile' => 'currencies-index-delete-mobile-' . $currency->id,
            ];
        })->all();
    }

    private function buildCurrencyBulkRows($bulkCurrencies): array
    {
        return $bulkCurrencies->values()->map(function (Currency $currency, int $index): array {
            return [
                'id' => (int) $currency->id,
                'code' => (string) ($currency->code ?? '-'),
                'name' => (string) ($currency->name ?? '-'),
                'rate_input_name' => 'rates[' . $index . '][rate_to_idr]',
                'rate_input_value' => old('rates.' . $index . '.rate_to_idr', $currency->rate_to_idr),
                'decimal_input_name' => 'rates[' . $index . '][decimal_places]',
                'decimal_input_value' => old('rates.' . $index . '.decimal_places', $currency->decimal_places),
                'id_input_name' => 'rates[' . $index . '][id]',
            ];
        })->all();
    }
}
