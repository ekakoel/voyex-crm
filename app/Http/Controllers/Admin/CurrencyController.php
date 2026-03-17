<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Currency::query();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $currencies = $query->orderByDesc('is_default')->orderBy('code')->paginate(15)->withQueryString();
        $bulkCurrencies = Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->take(10)
            ->get();

        return view('modules.currencies.index', compact('currencies', 'bulkCurrencies'));
    }

    public function create(): View
    {
        return view('modules.currencies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        if (($validated['rate_to_idr'] ?? 0) <= 0) {
            return back()->withErrors(['rate_to_idr' => 'Rate must be greater than 0.'])->withInput();
        }

        $currency = Currency::create($validated);

        if ($currency->is_default) {
            Currency::query()->where('id', '!=', $currency->id)->update(['is_default' => false]);
        }

        return redirect()->route('currencies.index')->with('success', 'Currency created.');
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
            return back()->withErrors(['rate_to_idr' => 'Rate must be greater than 0.'])->withInput();
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

        return redirect()->route('currencies.index')->with('success', 'Currency updated.');
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

        return back()->with('success', 'Bulk rate update saved.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->withErrors(['currency' => 'Default currency cannot be deleted.']);
        }
        $activeCount = Currency::query()->where('is_active', true)->count();
        if ($currency->is_active && $activeCount <= 1) {
            return back()->withErrors(['currency' => 'At least one active currency is required.']);
        }

        $currency->delete();

        return back()->with('success', 'Currency deleted.');
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
        if (! $user || ! $user->hasAnyRole(['Manager', 'Administrator', 'Super Admin'])) {
            abort(403, 'Only Manager/Administrator/Super Admin can update currency rates.');
        }
    }
}
