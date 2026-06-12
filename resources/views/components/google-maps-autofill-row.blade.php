@props([
    'label' => 'Google Maps URL',
    'name' => 'google_maps_url',
    'field' => 'google_maps_url',
    'id' => null,
    'value' => '',
    'placeholder' => 'https://maps.google.com/...',
    'errorKey' => null,
])

@php
    $resolvedErrorKey = $errorKey ?? $name;
@endphp

<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
    <div class="mt-1 flex flex-nowrap items-center gap-3">
        <input
            @if ($id) id="{{ $id }}" @endif
            name="{{ $name }}"
            type="url"
            data-location-field="{{ $field }}"
            data-location-map-url-input
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            class="app-input min-w-0 flex-1">
        <button
            type="button"
            data-location-autofill-trigger
            class="btn-outline-sm h-[var(--app-form-control-h)] shrink-0 px-3">
            Auto Fill
        </button>
    </div>
    @error($resolvedErrorKey) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>

@once
    @push('scripts')
        <script>
            function initLocationAutofill(root = document) {
                const resolvingMessage = @json(ui_phrase('Resolving location data from Google Maps URL...'));
                const successMessage = @json(ui_phrase('Location data filled successfully.'));
                const failedMessage = @json(ui_phrase('Unable to resolve this Google Maps link.'));
                const scope = root instanceof Element || root instanceof Document ? root : document;
                const containers = scope.matches?.('[data-location-autofill]')
                    ? [scope]
                    : Array.from(scope.querySelectorAll('[data-location-autofill]'));

                const fieldKeys = ['google_maps_url', 'location', 'city', 'province', 'country', 'address', 'latitude', 'longitude', 'timezone', 'destination_id'];

                const statusClasses = [
                    'text-gray-500',
                    'dark:text-gray-400',
                    'text-rose-600',
                    'dark:text-rose-400',
                    'text-emerald-600',
                    'dark:text-emerald-400',
                    'text-sky-600',
                    'dark:text-sky-400',
                ];

                containers.forEach((container) => {
                    if (container.dataset.locationAutofillBound === '1') {
                        return;
                    }
                    container.dataset.locationAutofillBound = '1';

                    const endpoint = String(container.getAttribute('data-location-resolve-url') || '').trim();
                    const mapUrlInput = container.querySelector('[data-location-map-url-input]');
                    const trigger = container.querySelector('[data-location-autofill-trigger]');
                    const statusNode = container.querySelector('[data-location-status]');
                    const destinationSelect = container.querySelector('[data-location-field="destination_id"]');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                    if (!endpoint || !mapUrlInput || !trigger) {
                        return;
                    }

                    let activeController = null;
                    let lastResolvedUrl = '';
                    let suppressAutoResolve = false;

                    const setStatus = (message, tone = 'neutral') => {
                        if (!statusNode) {
                            return;
                        }

                        const text = String(message || '').trim();
                        if (text === '') {
                            statusNode.textContent = '';
                            statusNode.classList.add('hidden');
                            statusNode.classList.remove(...statusClasses);
                            return;
                        }

                        statusNode.textContent = text;
                        statusNode.classList.remove('hidden', ...statusClasses);

                        if (tone === 'error') {
                            statusNode.classList.add('text-rose-600', 'dark:text-rose-400');
                            return;
                        }
                        if (tone === 'success') {
                            statusNode.classList.add('text-emerald-600', 'dark:text-emerald-400');
                            return;
                        }
                        if (tone === 'loading') {
                            statusNode.classList.add('text-sky-600', 'dark:text-sky-400');
                            return;
                        }

                        statusNode.classList.add('text-gray-500', 'dark:text-gray-400');
                    };

                    const dispatchFieldEvents = (field) => {
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    };

                    const applyResolvedData = (data) => {
                        if (!data || typeof data !== 'object') {
                            return;
                        }

                        fieldKeys.forEach((key) => {
                            const target = container.querySelector(`[data-location-field="${key}"]`);
                            if (!target) {
                                return;
                            }

                            const value = data[key];
                            if (value === undefined || value === null) {
                                return;
                            }

                            const nextValue = String(value);
                            if (target instanceof HTMLSelectElement) {
                                target.value = nextValue;
                                dispatchFieldEvents(target);
                                return;
                            }

                            if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) {
                                target.value = nextValue;
                                dispatchFieldEvents(target);
                            }
                        });
                    };

                    const syncDestinationContext = () => {
                        if (!(destinationSelect instanceof HTMLSelectElement)) {
                            return;
                        }

                        const selected = destinationSelect.selectedOptions?.[0];
                        if (!selected) {
                            return;
                        }

                        const cityInput = container.querySelector('[data-location-field="city"]');
                        const provinceInput = container.querySelector('[data-location-field="province"]');

                        if (cityInput instanceof HTMLInputElement && String(cityInput.value || '').trim() === '') {
                            const cityValue = String(selected.getAttribute('data-city') || '').trim();
                            if (cityValue !== '') {
                                cityInput.value = cityValue;
                                dispatchFieldEvents(cityInput);
                            }
                        }

                        if (provinceInput instanceof HTMLInputElement && String(provinceInput.value || '').trim() === '') {
                            const provinceValue = String(selected.getAttribute('data-province') || '').trim();
                            if (provinceValue !== '') {
                                provinceInput.value = provinceValue;
                                dispatchFieldEvents(provinceInput);
                            }
                        }
                    };

                    const resolveLocation = async ({ force = false } = {}) => {
                        const url = String(mapUrlInput.value || '').trim();
                        if (url === '') {
                            setStatus('', 'neutral');
                            return;
                        }

                        if (!force && suppressAutoResolve) {
                            return;
                        }

                        if (!force && url === lastResolvedUrl) {
                            return;
                        }

                        if (activeController) {
                            activeController.abort();
                        }

                        activeController = new AbortController();
                        setStatus(resolvingMessage, 'loading');

                        try {
                            const response = await fetch(endpoint, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({ google_maps_url: url }),
                                signal: activeController.signal,
                            });

                            const payload = await response.json().catch(() => null);
                            if (!response.ok || !payload?.data) {
                                throw new Error(payload?.message || failedMessage);
                            }

                            suppressAutoResolve = true;
                            applyResolvedData(payload.data);
                            syncDestinationContext();
                            lastResolvedUrl = String(payload.data.google_maps_url || url).trim();
                            setStatus(successMessage, 'success');
                        } catch (error) {
                            if (error?.name === 'AbortError') {
                                return;
                            }
                            setStatus(error?.message || failedMessage, 'error');
                        } finally {
                            window.setTimeout(() => {
                                suppressAutoResolve = false;
                            }, 0);
                        }
                    };

                    trigger.addEventListener('click', () => {
                        resolveLocation({ force: true });
                    });

                    mapUrlInput.addEventListener('blur', () => {
                        resolveLocation({ force: false });
                    });

                    mapUrlInput.addEventListener('change', () => {
                        resolveLocation({ force: false });
                    });

                    if (destinationSelect instanceof HTMLSelectElement) {
                        destinationSelect.addEventListener('change', syncDestinationContext);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                initLocationAutofill(document);
            });
        </script>
    @endpush
@endonce
