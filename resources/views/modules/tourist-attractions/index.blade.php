@extends('layouts.master')
@section('page_title', __('ui.modules.tourist_attractions.page_title'))
@section('page_subtitle', __('ui.modules.tourist_attractions.page_subtitle'))
@section('page_actions')
    <a href="{{ route('tourist-attractions.create') }}" class="btn-primary">{{ __('ui.modules.tourist_attractions.add_attraction') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--tourist-attractions" data-tourist-attractions-index data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                @if (($googleImportDefaults['can_import'] ?? false) === true)
                    <div class="app-card p-5 space-y-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.tourist_attractions.import_google.title') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.tourist_attractions.import_google.subtitle') }}</p>
                        </div>
                        @if (!($googleImportDefaults['is_configured'] ?? false))
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                                {{ __('ui.modules.tourist_attractions.import_google.api_key_hint') }}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('tourist-attractions.import-google') }}" class="grid grid-cols-1 gap-3">
                            @csrf
                            <select name="destination_id" class="app-input" required>
                                <option value="">{{ __('ui.modules.tourist_attractions.import_google.select_destination') }}</option>
                                @foreach (($destinations ?? collect()) as $destination)
                                    <option value="{{ $destination->id }}" @selected((string) old('destination_id') === (string) $destination->id)>
                                        {{ $destination->name }}{{ ($destination->city || $destination->province) ? ' ('.trim(($destination->city ?? '-').' / '.($destination->province ?? '-')).')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <input name="query" value="{{ old('query') }}" placeholder="{{ __('ui.modules.tourist_attractions.import_google.custom_query_placeholder') }}" class="app-input">
                            <select name="island_key" class="app-input">
                                <option value="">{{ __('ui.modules.tourist_attractions.import_google.all_islands') }}</option>
                                @foreach (($importIslandOptions ?? []) as $islandKey => $islandMeta)
                                    <option value="{{ $islandKey }}" @selected((string) old('island_key') === (string) $islandKey)>
                                        {{ $islandMeta['label'] ?? $islandKey }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ __('ui.modules.tourist_attractions.import_google.categories') }}</p>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @php($selectedPlaceCategories = collect(old('place_categories', []))->map(fn ($value) => (string) $value)->all())
                                    @foreach (($importCategoryOptions ?? []) as $categoryKey => $categoryMeta)
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                            <input
                                                type="checkbox"
                                                name="place_categories[]"
                                                value="{{ $categoryKey }}"
                                                @checked(in_array((string) $categoryKey, $selectedPlaceCategories, true))
                                                class="rounded border-gray-300 text-indigo-600"
                                            >
                                            <span>{{ $categoryMeta['label'] ?? $categoryKey }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="number" name="max_results" min="1" max="200" value="{{ old('max_results', $googleImportDefaults['max_results'] ?? 60) }}" class="app-input" title="Max results">
                                <input name="region" maxlength="5" value="{{ old('region', $googleImportDefaults['region'] ?? 'ID') }}" class="app-input" title="Region code">
                                <div class="app-input bg-gray-50 text-xs text-gray-500 dark:bg-gray-800/50 dark:text-gray-300">{{ __('ui.modules.tourist_attractions.import_google.language_fixed_en') }}</div>
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <input type="checkbox" name="dry_run" value="1" @checked(old('dry_run')) class="rounded border-gray-300 text-indigo-600">
                                {{ __('ui.modules.tourist_attractions.import_google.dry_run') }}
                            </label>
                            <button type="submit" class="btn-primary w-full justify-center">{{ __('ui.modules.tourist_attractions.import_google.import_now') }}</button>
                        </form>
                    </div>
                @endif
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('tourist-attractions.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-tourist-attractions-index-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.modules.tourist_attractions.search') }}" class="app-input sm:col-span-2" data-tourist-attractions-filter-input>
                        <select name="destination_id" class="app-input sm:col-span-2" data-tourist-attractions-filter-input>
                            <option value="">{{ __('ui.common.all_destinations') }}</option>
                            @foreach (($destinations ?? collect()) as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>
                                    {{ $destination->name }}{{ ($destination->city || $destination->province) ? ' ('.trim(($destination->city ?? '-').' / '.($destination->province ?? '-')).')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-tourist-attractions-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('tourist-attractions.index') }}" class="btn-ghost" data-tourist-attractions-filter-reset>{{ __('ui.common.reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-tourist-attractions-index-results-wrap>
                @include('modules.tourist-attractions.partials._index-results', ['touristAttractions' => $touristAttractions])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            if (window.__touristAttractionToggleAjaxBound) {
                return;
            }
            window.__touristAttractionToggleAjaxBound = true;

            const pageSelector = '[data-tourist-attractions-index]';
            const resultsSelector = '[data-tourist-attractions-index-results-wrap]';
            const toggleFormSelector = '[data-tourist-attractions-toggle-form]';
            const deleteFormSelector = '[data-tourist-attractions-delete-form]';
            const touristAttractionAjaxMessages = {
                refreshFailed: @json(__('ui.modules.tourist_attractions.ajax.refresh_failed')),
                deleteConfirm: @json(__('ui.modules.tourist_attractions.ajax.delete_confirm')),
                requestFailed: @json(__('ui.modules.tourist_attractions.ajax.request_failed')),
                deleteSuccess: @json(__('ui.modules.tourist_attractions.ajax.delete_success')),
                statusSuccess: @json(__('ui.modules.tourist_attractions.ajax.status_success')),
            };

            const setLoading = (resultsWrap, loading) => {
                if (!resultsWrap) return;
                resultsWrap.classList.toggle('opacity-60', loading);
                resultsWrap.classList.toggle('pointer-events-none', loading);
            };

            const showNotice = (resultsWrap, message, tone = 'success') => {
                if (!resultsWrap || !message) return;
                const existing = resultsWrap.querySelector('[data-tourist-attractions-ajax-notice]');
                if (existing) {
                    existing.remove();
                }

                const notice = document.createElement('div');
                notice.setAttribute('data-tourist-attractions-ajax-notice', '1');
                notice.className = tone === 'error'
                    ? 'rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300'
                    : 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300';
                notice.textContent = message;
                resultsWrap.prepend(notice);
            };

            const refreshResults = async (resultsWrap) => {
                const response = await fetch(window.location.href, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Tourist-Attractions-Ajax': '1',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => null);
                if (!response.ok || !payload || typeof payload.html !== 'string') {
                    throw new Error(touristAttractionAjaxMessages.refreshFailed);
                }
                resultsWrap.innerHTML = payload.html;
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target instanceof HTMLFormElement ? event.target : null;
                if (!form || (!form.matches(toggleFormSelector) && !form.matches(deleteFormSelector))) {
                    return;
                }
                const isDeleteForm = form.matches(deleteFormSelector);

                const page = form.closest(pageSelector);
                const resultsWrap = page ? page.querySelector(resultsSelector) : null;
                if (!(resultsWrap instanceof HTMLElement)) {
                    return;
                }

                if (isDeleteForm) {
                    const confirmed = window.confirm(touristAttractionAjaxMessages.deleteConfirm);
                    if (!confirmed) {
                        return;
                    }
                }

                event.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-60', 'cursor-not-allowed');
                }
                setLoading(resultsWrap, true);

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Tourist-Attractions-Ajax': '1',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });
                    const payload = await response.json().catch(() => null);
                    if (!response.ok || !payload || payload.success !== true) {
                        throw new Error((payload && payload.message) ? String(payload.message) : touristAttractionAjaxMessages.requestFailed);
                    }

                    await refreshResults(resultsWrap);
                    showNotice(resultsWrap, String(payload.message || (isDeleteForm ? touristAttractionAjaxMessages.deleteSuccess : touristAttractionAjaxMessages.statusSuccess)), 'success');
                } catch (error) {
                    showNotice(resultsWrap, error instanceof Error ? error.message : touristAttractionAjaxMessages.requestFailed, 'error');
                } finally {
                    setLoading(resultsWrap, false);
                    if (submitButton instanceof HTMLButtonElement) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
                    }
                }
            });
        })();
    </script>
@endpush
