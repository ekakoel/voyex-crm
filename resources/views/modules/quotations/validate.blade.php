@extends('layouts.master')

@section('page_title', __('ui.modules.quotations.validate_page_title'))
@section('page_subtitle', __('ui.modules.quotations.validate_page_subtitle'))
@section('page_actions')
    <a href="{{ route('quotations.show', $quotation) }}" class="btn-secondary">{{ __('ui.common.view_detail') }}</a>
    <a href="{{ route('quotations.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
@endsection

@push('scripts')
    <script>
        (function() {
            const modal = document.getElementById('validation-item-detail-modal');
            if (!modal) return;

            const endpointTemplate = modal.getAttribute('data-detail-endpoint-template') || '';
            const contactUpdateEndpointTemplate = modal.getAttribute('data-contact-update-endpoint-template') || '';
            const titleEl = modal.querySelector('[data-modal-title]');
            const contactEl = modal.querySelector('[data-modal-contact]');
            const currentEl = modal.querySelector('[data-modal-current]');
            const loadingEl = modal.querySelector('[data-modal-loading]');
            const errorEl = modal.querySelector('[data-modal-error]');
            const contactNameInput = modal.querySelector('[data-contact-name]');
            const contactPhoneInput = modal.querySelector('[data-contact-phone]');
            const contactEmailInput = modal.querySelector('[data-contact-email]');
            const contactWebsiteInput = modal.querySelector('[data-contact-website]');
            const contactAddressDisplay = modal.querySelector('[data-contact-address-display]');
            const updateContactButton = modal.querySelector('[data-update-contact]');
            const updateContactSpinner = modal.querySelector('[data-update-contact-spinner]');
            const updateContactLabel = modal.querySelector('[data-update-contact-label]');
            const updateContactFeedback = modal.querySelector('[data-update-contact-feedback]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const appCurrency = String(window.appCurrency || 'IDR').toUpperCase();
            const appCurrencyRateToIdr = Number(window.appCurrencyRateToIdr || 1);
            const appCurrencySymbol = String(window.appCurrencySymbol || (appCurrency === 'USD' ? '$' : 'Rp'));
            const currencyBadgeText = appCurrencySymbol || appCurrency;
            const appDisplayLocale = appCurrency === 'USD' ? 'en-US' : 'id-ID';

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            };

            const readableItemType = (rawType) => {
                const type = String(rawType || '').trim();
                const map = {
                    FoodBeverage: 'Food and Beverage',
                    IslandTransfer: 'Island Transfer',
                    TouristAttraction: 'Tourist Attraction',
                    TransportUnit: 'Transport',
                };
                return map[type] || type || '-';
            };

            const setLoadingState = (isLoading) => {
                if (loadingEl) loadingEl.classList.toggle('hidden', !isLoading);
                if (errorEl) errorEl.classList.add('hidden');
                if (updateContactFeedback) {
                    updateContactFeedback.classList.add('hidden');
                    updateContactFeedback.textContent = '';
                }
                if (contactEl) contactEl.classList.toggle('hidden', isLoading);
                if (currentEl) currentEl.classList.toggle('hidden', isLoading);
            };

            const renderDetail = (payload) => {
                const item = payload.item || {};
                const contact = payload.contact || {};

                if (titleEl) {
                    const dayNumber = Number(item.day_number || 0);
                    const dayLabel = dayNumber > 0 ? `Day ${dayNumber}` : 'Without Day';
                    const typeLabel = readableItemType(item.serviceable_type);
                    const itemName = String(item.item_name || '').trim() || String(item.description || '').trim() || '-';
                    titleEl.textContent = `${dayLabel} - ${typeLabel} - ${itemName}`;
                }

                modal.setAttribute('data-current-item-id', String(item.id || ''));

                if (contactEl) {
                    const providerEl = contactEl.querySelector('[data-contact-provider]');
                    if (providerEl) {
                        providerEl.textContent = contact.vendor_provider_name || '-';
                    }
                    if (contactAddressDisplay) {
                        contactAddressDisplay.textContent = contact.contact_address || '-';
                    }
                }
                if (contactNameInput) contactNameInput.value = contact.contact_name && contact.contact_name !== '-' ? contact.contact_name : '';
                if (contactPhoneInput) contactPhoneInput.value = contact.contact_phone && contact.contact_phone !== '-' ? contact.contact_phone : '';
                if (contactEmailInput) contactEmailInput.value = contact.contact_email && contact.contact_email !== '-' ? contact.contact_email : '';
                if (contactWebsiteInput) contactWebsiteInput.value = contact.contact_website && contact.contact_website !== '-' ? contact.contact_website : '';

                if (currentEl) {
                    const normalizedMarkupType = String(item.markup_type || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                    const markupDisplay = normalizedMarkupType === 'percent'
                        ? `${Number(item.markup || 0).toLocaleString(appDisplayLocale, { maximumFractionDigits: 2 })}%`
                        : formatMoneyFromIdr(item.markup);
                    const updatedAtText = (() => {
                        const iso = String(item.updated_at || '').trim();
                        if (!iso) return '-';
                        const parsed = new Date(iso);
                        if (Number.isNaN(parsed.getTime())) return '-';
                        const yyyy = parsed.getFullYear();
                        const mm = String(parsed.getMonth() + 1).padStart(2, '0');
                        const dd = String(parsed.getDate()).padStart(2, '0');
                        const hh = String(parsed.getHours()).padStart(2, '0');
                        const ii = String(parsed.getMinutes()).padStart(2, '0');
                        return `${yyyy}-${mm}-${dd} (${hh}:${ii})`;
                    })();

                    currentEl.innerHTML = `
                        <p class="mb-2 text-[11px] text-gray-500 dark:text-gray-400">{{ __('This section shows the rate currently used by this quotation item.') }}</p>
                        <div><span class="font-semibold">{{ __('ui.modules.quotations.active_contract_rate') }}:</span> ${formatMoneyFromIdr(item.contract_rate)}</div>
                        <div class="mt-1"><span class="font-semibold">{{ __('ui.modules.quotations.active_markup_type') }}:</span> ${normalizedMarkupType}</div>
                        <div class="mt-1"><span class="font-semibold">{{ __('ui.modules.quotations.active_markup') }}:</span> ${markupDisplay}</div>
                        <div class="mt-1"><span class="font-semibold">{{ __('ui.common.updated_by') }}:</span> ${item.validator || '-'}</div>
                        <div class="mt-1"><span class="font-semibold">{{ __('ui.common.updated_at') }}:</span> ${updatedAtText}</div>
                    `;
                }

            };

            const loadDetail = async (itemId) => {
                if (!endpointTemplate) return;
                const endpoint = endpointTemplate.replace('__ITEM__', String(itemId));

                setLoadingState(true);
                openModal();

                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    setLoadingState(false);
                    renderDetail(payload);
                } catch (error) {
                    setLoadingState(false);
                    if (errorEl) {
                        errorEl.classList.remove('hidden');
                        errorEl.textContent = '{{ __('ui.modules.quotations.load_detail_failed') }}';
                    }
                }
            };

            document.querySelectorAll('[data-open-validation-detail]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const itemId = btn.getAttribute('data-open-validation-detail');
                    if (!itemId) return;
                    loadDetail(itemId);
                });
            });

            document.querySelectorAll('[data-close-validation-modal]').forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            const setUpdateContactLoading = (isLoading) => {
                if (updateContactButton) {
                    updateContactButton.disabled = isLoading;
                }
                if (updateContactSpinner) {
                    updateContactSpinner.classList.toggle('hidden', !isLoading);
                }
                if (updateContactLabel) {
                    updateContactLabel.textContent = '{{ __('ui.modules.quotations.update_contact') }}';
                }
            };

            const setUpdateContactFeedback = (message, type = 'success') => {
                if (!updateContactFeedback) return;
                if (!message) {
                    updateContactFeedback.classList.add('hidden');
                    updateContactFeedback.textContent = '';
                    return;
                }
                updateContactFeedback.classList.remove('hidden', 'text-emerald-700', 'text-rose-700', 'dark:text-emerald-300', 'dark:text-rose-300');
                updateContactFeedback.textContent = message;
                if (type === 'error') {
                    updateContactFeedback.classList.add('text-rose-700', 'dark:text-rose-300');
                } else {
                    updateContactFeedback.classList.add('text-emerald-700', 'dark:text-emerald-300');
                }
            };

            const updateContactAjax = async () => {
                const itemId = modal.getAttribute('data-current-item-id') || '';
                if (!itemId || !contactUpdateEndpointTemplate) return;

                const endpoint = contactUpdateEndpointTemplate.replace('__ITEM__', String(itemId));
                const payload = {
                    contact_name: contactNameInput?.value ?? '',
                    contact_phone: contactPhoneInput?.value ?? '',
                    contact_email: contactEmailInput?.value ?? '',
                    contact_website: contactWebsiteInput?.value ?? '',
                };

                setUpdateContactFeedback('');
                setUpdateContactLoading(true);
                try {
                    const response = await fetch(endpoint, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        const firstError = result?.errors ? Object.values(result.errors)[0]?.[0] : null;
                        throw new Error(firstError || result?.message || 'Failed to update contact details.');
                    }

                    const contact = result?.contact || {};
                    if (contactNameInput) contactNameInput.value = contact.contact_name && contact.contact_name !== '-' ? contact.contact_name : '';
                    if (contactPhoneInput) contactPhoneInput.value = contact.contact_phone && contact.contact_phone !== '-' ? contact.contact_phone : '';
                    if (contactEmailInput) contactEmailInput.value = contact.contact_email && contact.contact_email !== '-' ? contact.contact_email : '';
                    if (contactWebsiteInput) contactWebsiteInput.value = contact.contact_website && contact.contact_website !== '-' ? contact.contact_website : '';
                    if (contactAddressDisplay) contactAddressDisplay.textContent = contact.contact_address || '-';

                    setUpdateContactFeedback(result?.message || 'Contact details updated.');
                } catch (error) {
                    setUpdateContactFeedback(error.message || 'Failed to update contact details.', 'error');
                } finally {
                    setUpdateContactLoading(false);
                }
            };

            updateContactButton?.addEventListener('click', updateContactAjax);

            const setItemFeedback = (itemId, message, type = 'success') => {
                const elements = document.querySelectorAll(`[data-item-feedback="${itemId}"]`);
                if (!elements.length) return;

                elements.forEach((el) => {
                    if (!message) {
                        el.textContent = '';
                        el.classList.add('hidden');
                        return;
                    }

                    el.textContent = message;
                    el.classList.remove('hidden', 'text-emerald-700', 'text-rose-700', 'dark:text-emerald-300', 'dark:text-rose-300');
                    if (type === 'error') {
                        el.classList.add('text-rose-700', 'dark:text-rose-300');
                    } else {
                        el.classList.add('text-emerald-700', 'dark:text-emerald-300');
                    }
                });
            };

            const formatDateTime = (isoDate) => {
                if (!isoDate) return '-';
                const parsed = new Date(isoDate);
                if (Number.isNaN(parsed.getTime())) return isoDate;
                const parts = new Intl.DateTimeFormat('en-CA', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                    timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                }).formatToParts(parsed);
                const map = Object.fromEntries(parts.map((part) => [part.type, part.value]));
                return `${map.year}-${map.month}-${map.day} (${map.hour}:${map.minute})`;
            };

            const parseIntegerFromDisplay = (value) => {
                const digits = String(value ?? '').replace(/[^\d]/g, '');
                if (digits === '') return 0;
                return Number.parseInt(digits, 10) || 0;
            };

            const fromIdrToDisplayInteger = (value) => {
                const idrValue = Math.max(0, Number(value || 0));
                if (!Number.isFinite(idrValue)) return 0;
                if (appCurrency === 'IDR' || !Number.isFinite(appCurrencyRateToIdr) || appCurrencyRateToIdr <= 0) {
                    return Math.round(idrValue);
                }

                return Math.round(idrValue / appCurrencyRateToIdr);
            };

            const toIdrInteger = (displayValue) => {
                const numericDisplay = Math.max(0, Number(displayValue || 0));
                if (!Number.isFinite(numericDisplay)) return 0;
                if (appCurrency === 'IDR' || !Number.isFinite(appCurrencyRateToIdr) || appCurrencyRateToIdr <= 0) {
                    return Math.round(numericDisplay);
                }

                return Math.round(numericDisplay * appCurrencyRateToIdr);
            };

            const formatIntegerDisplay = (value) => {
                const num = parseIntegerFromDisplay(value);
                return num.toLocaleString(appDisplayLocale);
            };

            const formatMoneyFromIdr = (value) => {
                const displayValue = fromIdrToDisplayInteger(value);
                const formattedValue = Number(displayValue).toLocaleString(appDisplayLocale, {
                    maximumFractionDigits: 0,
                });
                if (appCurrency === 'USD') {
                    return `${appCurrencySymbol}${formattedValue}`;
                }

                return `${appCurrencySymbol} ${formattedValue}`;
            };

            const convertInputFromIdrToDisplayCurrency = (input) => {
                if (!input) return;
                const idrValue = parseIntegerFromDisplay(input.value);
                const displayValue = fromIdrToDisplayInteger(idrValue);
                input.value = formatIntegerDisplay(displayValue);
            };

            const getCanonicalInput = (itemId, field) => {
                return document.querySelector(`[data-canonical-input="${field}-${itemId}"]`)
                    || document.querySelector(`[name="items[${itemId}][${field}]"]`);
            };

            const setTextForSelectors = (selector, text) => {
                document.querySelectorAll(selector).forEach((el) => {
                    el.textContent = text;
                });
            };

            const refreshMarkupBadgesForItem = (itemId) => {
                const canonicalTypeInput = getCanonicalInput(itemId, 'markup_type');
                const mobileTypeInput = document.querySelector(`[data-mobile-markup-type="${itemId}"]`);
                const selectedType = String(canonicalTypeInput?.value || mobileTypeInput?.value || 'fixed').toLowerCase() === 'percent'
                    ? 'percent'
                    : 'fixed';
                const badgeText = selectedType === 'percent' ? '%' : currencyBadgeText;
                setTextForSelectors(`[data-markup-badge="${itemId}"], [data-mobile-markup-badge="${itemId}"]`, badgeText);
            };

            const setActionButtonLoading = (button, isLoading, spinnerSelector, labelSelector) => {
                if (!button) return;
                button.disabled = isLoading;
                const spinnerEl = button.querySelector(spinnerSelector);
                const labelEl = button.querySelector(labelSelector);
                if (spinnerEl) spinnerEl.classList.toggle('hidden', !isLoading);
                if (labelEl) labelEl.classList.toggle('hidden', isLoading);
            };

            const refreshAllMoneyBadges = () => {
                setTextForSelectors('[data-contract-rate-badge]', currencyBadgeText);
                setTextForSelectors('[data-mobile-contract-rate-badge]', currencyBadgeText);
                document.querySelectorAll('[data-canonical-input^="markup_type-"]').forEach((input) => {
                    const itemId = String(input.getAttribute('data-canonical-input') || '').replace('markup_type-', '');
                    if (itemId !== '') {
                        refreshMarkupBadgesForItem(itemId);
                    }
                });
            };

            const syncMobileFieldsToCanonical = (itemId) => {
                const mobileContainer = document.querySelector('[data-mobile-validation-list]');
                if (mobileContainer && mobileContainer.offsetParent === null) {
                    return;
                }

                const mobileRateInput = document.querySelector(`[data-mobile-contract-rate="${itemId}"]`);
                const mobileMarkupTypeInput = document.querySelector(`[data-mobile-markup-type="${itemId}"]`);
                const mobileMarkupInput = document.querySelector(`[data-mobile-markup="${itemId}"]`);
                const mobileValidatedInput = document.querySelector(`[data-item-validated-checkbox="${itemId}"]:not([name])`);

                const canonicalRateInput = getCanonicalInput(itemId, 'contract_rate');
                const canonicalMarkupTypeInput = getCanonicalInput(itemId, 'markup_type');
                const canonicalMarkupInput = getCanonicalInput(itemId, 'markup');
                const canonicalValidatedInput = document.querySelector(`input[type="checkbox"][name="items[${itemId}][is_validated]"][value="1"]`);

                if (mobileRateInput && canonicalRateInput) {
                    canonicalRateInput.value = formatIntegerDisplay(mobileRateInput.value);
                }
                if (mobileMarkupTypeInput && canonicalMarkupTypeInput) {
                    canonicalMarkupTypeInput.value = mobileMarkupTypeInput.value || 'fixed';
                }
                if (mobileMarkupInput && canonicalMarkupInput) {
                    canonicalMarkupInput.value = formatIntegerDisplay(mobileMarkupInput.value);
                }
                if (mobileValidatedInput && canonicalValidatedInput) {
                    canonicalValidatedInput.checked = mobileValidatedInput.checked;
                }
            };

            const setItemButtonLoading = (itemId, isLoading) => {
                document.querySelectorAll(`[data-save-item="${itemId}"]`).forEach((btnEl) => {
                    btnEl.disabled = isLoading;
                    const spinnerEl = btnEl.querySelector(`[data-item-spinner="${itemId}"]`);
                    const labelEl = btnEl.querySelector(`[data-item-save-label="${itemId}"]`);
                    if (spinnerEl) spinnerEl.classList.toggle('hidden', !isLoading);
                    if (labelEl) labelEl.classList.toggle('hidden', isLoading);
                });
            };

            const saveItemAjax = async (button) => {
                const itemId = button.getAttribute('data-save-item');
                const url = button.getAttribute('data-save-item-url');
                if (!itemId || !url) return;

                syncMobileFieldsToCanonical(itemId);

                const contractRateInput = getCanonicalInput(itemId, 'contract_rate');
                const markupTypeInput = getCanonicalInput(itemId, 'markup_type');
                const markupInput = getCanonicalInput(itemId, 'markup');
                const normalizedContractRateDisplay = Math.max(0, parseIntegerFromDisplay(contractRateInput?.value || 0));
                const normalizedMarkupDisplay = Math.max(0, parseIntegerFromDisplay(markupInput?.value || 0));
                const normalizedContractRate = toIdrInteger(normalizedContractRateDisplay);
                const normalizedMarkup = toIdrInteger(normalizedMarkupDisplay);
                if (contractRateInput) {
                    contractRateInput.value = formatIntegerDisplay(normalizedContractRateDisplay);
                }
                if (markupInput) {
                    markupInput.value = formatIntegerDisplay(normalizedMarkupDisplay);
                }

                const payload = {
                    _method: 'PATCH',
                    contract_rate: normalizedContractRate,
                    markup_type: markupTypeInput?.value ?? 'fixed',
                    markup: normalizedMarkup,
                    // Save Item now implies item is validated once data is valid and saved.
                    is_validated: 1,
                };

                setItemButtonLoading(itemId, true);
                setItemFeedback(itemId, '');

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json();
                    if (!response.ok) {
                        const firstError = result?.errors ? Object.values(result.errors)[0]?.[0] : null;
                        throw new Error(firstError || result?.message || 'Failed to save item validation.');
                    }

                    updateProgressUi(result.progress || {});
                    updateItemRowUi(itemId, result.item || {});
                    setItemFeedback(itemId, '');
                } catch (error) {
                    setItemFeedback(itemId, error.message || 'Failed to save item validation.', 'error');
                } finally {
                    setItemButtonLoading(itemId, false);
                }
            };

            document.querySelectorAll('[data-save-item]').forEach((btn) => {
                btn.addEventListener('click', () => saveItemAjax(btn));
            });

            document.querySelectorAll('[data-markup-input]').forEach((input) => {
                convertInputFromIdrToDisplayCurrency(input);
                input.addEventListener('input', () => {
                    input.value = formatIntegerDisplay(input.value);
                });
                input.addEventListener('blur', () => {
                    input.value = formatIntegerDisplay(input.value);
                });
            });

            document.querySelectorAll('[data-contract-rate-input]').forEach((input) => {
                convertInputFromIdrToDisplayCurrency(input);
                input.addEventListener('input', () => {
                    input.value = formatIntegerDisplay(input.value);
                });
                input.addEventListener('blur', () => {
                    input.value = formatIntegerDisplay(input.value);
                });
            });

            document.querySelectorAll('[data-mobile-markup]').forEach((input) => {
                convertInputFromIdrToDisplayCurrency(input);
                const itemId = input.getAttribute('data-mobile-markup');
                input.addEventListener('input', () => {
                    input.value = formatIntegerDisplay(input.value);
                    if (itemId) syncMobileFieldsToCanonical(itemId);
                });
                input.addEventListener('blur', () => {
                    input.value = formatIntegerDisplay(input.value);
                    if (itemId) syncMobileFieldsToCanonical(itemId);
                });
            });

            document.querySelectorAll('[data-mobile-contract-rate]').forEach((input) => {
                convertInputFromIdrToDisplayCurrency(input);
                const itemId = input.getAttribute('data-mobile-contract-rate');
                input.addEventListener('input', () => {
                    input.value = formatIntegerDisplay(input.value);
                    if (itemId) syncMobileFieldsToCanonical(itemId);
                });
                input.addEventListener('blur', () => {
                    input.value = formatIntegerDisplay(input.value);
                    if (itemId) syncMobileFieldsToCanonical(itemId);
                });
            });

            document.querySelectorAll('[data-mobile-markup-type]').forEach((select) => {
                const itemId = select.getAttribute('data-mobile-markup-type');
                select.addEventListener('change', () => {
                    if (itemId) {
                        syncMobileFieldsToCanonical(itemId);
                        refreshMarkupBadgesForItem(itemId);
                    }
                });
            });

            document.querySelectorAll('[data-canonical-input^="markup_type-"]').forEach((select) => {
                const itemId = String(select.getAttribute('data-canonical-input') || '').replace('markup_type-', '');
                select.addEventListener('change', () => {
                    if (itemId) {
                        refreshMarkupBadgesForItem(itemId);
                    }
                });
            });

            document.querySelectorAll('[data-item-validated-checkbox]:not([name])').forEach((checkbox) => {
                const itemId = checkbox.getAttribute('data-item-validated-checkbox');
                checkbox.addEventListener('change', () => {
                    if (itemId) syncMobileFieldsToCanonical(itemId);
                });
            });

            const progressForm = document.querySelector('[data-validation-progress-form]');
            const saveProgressButton = document.querySelector('[data-save-progress-btn]');
            const finalizeButton = document.querySelector('[data-finalize-quotation-btn]');
            const finalizeForm = document.getElementById('quotation-finalize-form');

            progressForm?.addEventListener('submit', () => {
                document.querySelectorAll('[data-mobile-contract-rate]').forEach((input) => {
                    const itemId = input.getAttribute('data-mobile-contract-rate');
                    if (itemId) syncMobileFieldsToCanonical(itemId);
                });
                document.querySelectorAll('[data-contract-rate-input]').forEach((input) => {
                    const normalizedDisplay = Math.max(0, parseIntegerFromDisplay(input.value));
                    input.value = String(toIdrInteger(normalizedDisplay));
                });
                document.querySelectorAll('[data-markup-input]').forEach((input) => {
                    const normalizedDisplay = Math.max(0, parseIntegerFromDisplay(input.value));
                    input.value = String(toIdrInteger(normalizedDisplay));
                });
                setActionButtonLoading(saveProgressButton, true, '[data-save-progress-spinner]', '[data-save-progress-label]');
            });

            finalizeButton?.addEventListener('click', (event) => {
                event.preventDefault();
                if (!finalizeForm) return;
                finalizeButton.disabled = true;
                if (saveProgressButton) {
                    saveProgressButton.disabled = true;
                }
                finalizeForm.requestSubmit();
            });

            const updateProgressUi = (progress) => {
                if (!progress || typeof progress !== 'object') return;
                const totalValidatedEl = document.querySelector('[data-progress-total-validated]');
                const percentEl = document.querySelector('[data-progress-percent]');
                const statusEl = document.querySelector('[data-progress-status]');

                if (totalValidatedEl && progress.total_validated !== undefined) {
                    totalValidatedEl.textContent = String(progress.total_validated);
                }
                if (percentEl && progress.validation_percent !== undefined) {
                    percentEl.textContent = `${Number(progress.validation_percent || 0)}%`;
                }
                if (statusEl && progress.status) {
                    statusEl.textContent = String(progress.status);
                }
                if (finalizeButton && progress.is_complete !== undefined) {
                    finalizeButton.classList.toggle('hidden', !Boolean(progress.is_complete));
                }
            };

            const updateItemRowUi = (itemId, item) => {
                const statusCells = document.querySelectorAll(`[data-item-status="${itemId}"]`);
                const updatedCells = document.querySelectorAll(`[data-item-updated="${itemId}"]`);
                const validatedCheckboxes = document.querySelectorAll(`[data-item-validated-checkbox="${itemId}"]`);

                statusCells.forEach((statusCell) => {
                    if (item.is_validated) {
                        statusCell.innerHTML = `<span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('ui.modules.quotations.validated') }}</span>`;
                    } else {
                        statusCell.innerHTML = `<span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ __('ui.modules.quotations.pending_validation') }}</span>`;
                    }
                });

                updatedCells.forEach((updatedCell) => {
                    const validatorName = item.validator_name || '-';
                    const updatedAt = formatDateTime(item.updated_at);
                    updatedCell.innerHTML = `${validatorName}<br>${updatedAt}`;
                });

                validatedCheckboxes.forEach((validatedCheckbox) => {
                    validatedCheckbox.checked = Boolean(item.is_validated);
                });

                refreshMarkupBadgesForItem(itemId);
            };

            refreshAllMoneyBadges();
        })();
    </script>
@endpush

@section('content')
    @php
        $resolveMealLabelsFromMeta = static function (array $meta): array {
            $extractTokens = static function ($value): array {
                if (is_array($value)) {
                    $raw = implode(' ', array_map(static fn ($entry) => (string) $entry, $value));
                } else {
                    $raw = (string) $value;
                }

                return array_values(array_filter(array_map(
                    static fn ($part) => strtolower(trim((string) $part)),
                    preg_split('/[\s,;\/|]+/', $raw) ?: []
                )));
            };

            $tokens = array_merge(
                $extractTokens($meta['meal_type'] ?? ''),
                $extractTokens($meta['meal_period'] ?? '')
            );
            $tokens = array_values(array_unique($tokens));

            $labels = [];
            foreach (['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'] as $key => $label) {
                if (in_array($key, $tokens, true)) {
                    $labels[] = $label;
                }
            }

            return $labels;
        };
    @endphp
    <div class="space-y-6 module-page module-page--quotations">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="app-card p-6 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.number') }}</p>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</h2>
                </div>
                <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                    <div>{{ __('ui.common.status') }}: <span class="font-semibold">{{ $quotation->status }}</span></div>
                    <div>{{ __('ui.modules.quotations.validation_status') }}: <span class="font-semibold" data-progress-status>{{ $quotation->validation_status ?? 'pending' }}</span></div>
                </div>
            </div>

            <div class="module-kpi-grid">
                <div class="app-kpi-card rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.total_items') }}</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">{{ (int) ($progress['total_items'] ?? 0) }}</p>
                </div>
                <div class="app-kpi-card rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.total_required_validation') }}</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">{{ (int) ($progress['total_required'] ?? 0) }}</p>
                </div>
                <div class="app-kpi-card rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.total_validated_items') }}</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100" data-progress-total-validated>{{ (int) ($progress['total_validated'] ?? 0) }}</p>
                </div>
                <div class="app-kpi-card rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.validation_progress') }}</p>
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100" data-progress-percent>{{ (int) ($progress['validation_percent'] ?? 0) }}%</p>
                </div>
                <div class="app-kpi-card rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                    <p class="text-xs text-emerald-700 dark:text-emerald-300">{{ __('ui.common.customer') }}</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ $quotation->inquiry?->customer?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('quotations.validate.save-progress', $quotation) }}" class="app-card p-6 space-y-4" data-validation-progress-form>
            @csrf
            @method('PATCH')

            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.quotations.validation_items') }}</h3>
            </div>

            @php
                $resolveDayNumber = function ($item): int {
                    $dayNumber = (int) ($item->day_number ?? 0);
                    return $dayNumber > 0 ? $dayNumber : PHP_INT_MAX;
                };

                $sortedValidationItems = collect($validationItems ?? [])
                    ->sort(function ($a, $b) use ($resolveDayNumber): int {
                        $dayCompare = $resolveDayNumber($a) <=> $resolveDayNumber($b);
                        if ($dayCompare !== 0) {
                            return $dayCompare;
                        }

                        return ((int) ($a->id ?? 0)) <=> ((int) ($b->id ?? 0));
                    })
                    ->values();

                $groupedValidationItems = $sortedValidationItems->groupBy(function ($item) use ($resolveDayNumber): string {
                    $dayNumber = $resolveDayNumber($item);
                    return $dayNumber !== PHP_INT_MAX ? ('day-' . $dayNumber) : 'day-without';
                });
            @endphp

            <div class="responsive-data-shell">
            <div class="space-y-4 responsive-data-mobile" data-mobile-validation-list>
                @if ($groupedValidationItems->isNotEmpty())
                    @foreach ($groupedValidationItems as $dayItems)
                        @php
                            $firstDayItem = $dayItems->first();
                            $groupDayNumber = (int) ($firstDayItem->day_number ?? 0);
                            $dayText = $groupDayNumber > 0 ? ('Day ' . $groupDayNumber) : 'Without Day';
                        @endphp
                        <div class="responsive-group-card">
                            <div class="responsive-group-header">
                                {{ $dayText }}
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($dayItems as $item)
                                    @php
                                        $serviceableType = class_basename((string) ($item->serviceable_type ?? ''));
                                        $typeLabelMap = [
                                            'Activity' => 'Activity',
                                            'FoodBeverage' => 'Food and Beverage',
                                            'IslandTransfer' => 'Island Transfer',
                                            'Transport' => 'Transport',
                                            'TransportUnit' => 'Transport',
                                            'TouristAttraction' => 'Tourist Attraction',
                                            'HotelRoom' => 'Hotel',
                                        ];
                                        $typeLabel = $typeLabelMap[$serviceableType] ?? $serviceableType;
                                        $itemName = trim((string) ($item->serviceable?->name ?? ''));
                                        if ($itemName === '' && $serviceableType === 'HotelRoom') {
                                            $itemName = trim((string) ($item->serviceable?->rooms ?? ''));
                                        }
                                        $descriptionLabel = $itemName !== '' ? $itemName : '-';
                                        $vendorProviderItemLabel = $item->serviceable?->name ?? '-';

                                        if (in_array($serviceableType, ['Activity', 'FoodBeverage', 'IslandTransfer', 'Transport', 'TransportUnit'], true)) {
                                            $vendorName = trim((string) ($item->serviceable?->vendor?->name ?? ''));
                                            if ($vendorName !== '') {
                                                $vendorProviderItemLabel = $vendorName;
                                            }
                                        } elseif ($serviceableType === 'HotelRoom') {
                                            $hotelName = trim((string) ($item->serviceable?->hotel?->name ?? ''));
                                            if ($hotelName !== '') {
                                                $vendorProviderItemLabel = $hotelName;
                                            }

                                            $roomName = trim((string) ($item->serviceable?->rooms ?? $item->serviceable?->name ?? ''));
                                            if ($roomName !== '') {
                                                $descriptionLabel = $roomName;
                                            }
                                        }

                                        if ($serviceableType === 'FoodBeverage') {
                                            $serviceableMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                            $mealLabels = $resolveMealLabelsFromMeta($serviceableMeta);
                                            if ($mealLabels === []) {
                                                $startTime = trim((string) ($serviceableMeta['start_time'] ?? ''));
                                                if (preg_match('/^(\d{1,2}):(\d{2})$/', $startTime, $matches)) {
                                                    $hour = (int) $matches[1];
                                                    if ($hour <= 10) {
                                                        $mealLabels = ['Breakfast'];
                                                    } elseif ($hour <= 15) {
                                                        $mealLabels = ['Lunch'];
                                                    } else {
                                                        $mealLabels = ['Dinner'];
                                                    }
                                                }
                                            }
                                            if ($mealLabels !== []) {
                                                $descriptionLabel .= ' (' . implode(', ', $mealLabels) . ')';
                                            }
                                        }
                                    @endphp
                                    <div class="responsive-item-card space-y-3" data-validation-item-row="{{ $item->id }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <button type="button" data-open-validation-detail="{{ $item->id }}" class="text-left text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">
                                                {{ $vendorProviderItemLabel }}
                                            </button>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    value="1"
                                                    data-item-validated-checkbox="{{ $item->id }}"
                                                    @checked((bool) ($item->is_validated ?? false))
                                                    class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                                >
                                                <button type="button" class="btn-outline-sm" data-save-item="{{ $item->id }}" data-save-item-url="{{ route('quotations.validate.save-item', ['quotation' => $quotation, 'item' => $item]) }}">
                                                    <span data-item-spinner="{{ $item->id }}" class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                                                    <span data-item-save-label="{{ $item->id }}">{{ __('ui.modules.quotations.validate') }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                            <div>{{ __('ui.common.type') }}</div><div class="text-right text-gray-800 dark:text-gray-100">{{ $typeLabel }}</div>
                                            <div>{{ __('ui.common.description') }}</div><div class="text-right text-gray-800 dark:text-gray-100">{{ $descriptionLabel }}</div>
                                            <div>{{ __('ui.common.qty') }}</div><div class="text-right text-gray-800 dark:text-gray-100">{{ (int) ($item->qty ?? 0) }}</div>
                                            <div>{{ __('ui.modules.quotations.contract_rate') }}</div>
                                            <div>
                                                <div class="input-with-left-affix">
                                                    <input
                                                        type="text"
                                                        pattern="[0-9.]*"
                                                        inputmode="numeric"
                                                        data-mobile-contract-rate="{{ $item->id }}"
                                                        value="{{ old('items.' . $item->id . '.contract_rate', number_format((float) ($item->contract_rate ?? 0), 0, ',', '.')) }}"
                                                        class="app-input pl-14 text-right text-xs"
                                                    >
                                                    <span
                                                        data-mobile-contract-rate-badge="{{ $item->id }}"
                                                        class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                    ></span>
                                                </div>
                                            </div>
                                            <div>{{ __('ui.modules.quotations.markup_type') }}</div>
                                            <div>
                                                <select data-mobile-markup-type="{{ $item->id }}" class="app-input text-xs">
                                                    <option value="fixed" @selected(old('items.' . $item->id . '.markup_type', $item->markup_type) === 'fixed')>{{ __('ui.common.fixed') }}</option>
                                                    <option value="percent" @selected(old('items.' . $item->id . '.markup_type', $item->markup_type) === 'percent')>{{ __('ui.common.percent') }}</option>
                                                </select>
                                            </div>
                                            <div>{{ __('ui.modules.quotations.markup') }}</div>
                                            <div>
                                                <div class="input-with-left-affix">
                                                    <input
                                                        type="text"
                                                        pattern="[0-9.]*"
                                                        inputmode="numeric"
                                                        data-mobile-markup="{{ $item->id }}"
                                                        value="{{ old('items.' . $item->id . '.markup', number_format((float) ($item->markup ?? 0), 0, ',', '.')) }}"
                                                        class="app-input pl-14 text-right text-xs"
                                                    >
                                                    <span
                                                        data-mobile-markup-badge="{{ $item->id }}"
                                                        class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between gap-2 text-xs">
                                            <div data-item-updated="{{ $item->id }}" class="text-gray-600 dark:text-gray-300">
                                                {{ $item->validator?->name ?? '-' }}
                                                @if ($item->updated_at)
                                                    <br><x-local-time :value="$item->updated_at" />
                                                @endif
                                            </div>
                                            <div data-item-status="{{ $item->id }}">
                                                @if ((bool) ($item->is_validated ?? false))
                                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('ui.modules.quotations.validated') }}</span>
                                                @else
                                                    <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ __('ui.modules.quotations.pending_validation') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <p data-item-feedback="{{ $item->id }}" class="hidden text-[11px]"></p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        {{ __('ui.modules.quotations.no_validation_required_items') }}
                    </div>
                @endif
            </div>

            <div class="responsive-data-desktop overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.mark_validated') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.type') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.vendor_provider_item') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.description') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.qty') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.contract_rate') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.markup_type') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.markup') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Validated by') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.quotations.validation_status') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @if ($groupedValidationItems->isNotEmpty())
                        @foreach ($groupedValidationItems as $dayKey => $dayItems)
                            @php
                                $firstDayItem = $dayItems->first();
                                $groupDayNumber = (int) ($firstDayItem->day_number ?? 0);
                                $dayText = $groupDayNumber > 0 ? ('Day ' . $groupDayNumber) : 'Without Day';
                            @endphp
                            <tr class="bg-gray-50/90 dark:bg-gray-800/70">
                                <td colspan="11" class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">
                                    {{ $dayText }}
                                </td>
                            </tr>
                            @foreach ($dayItems as $item)
                            @php
                                $serviceableType = class_basename((string) ($item->serviceable_type ?? ''));
                                $typeLabelMap = [
                                    'Activity' => 'Activity',
                                    'FoodBeverage' => 'Food and Beverage',
                                    'IslandTransfer' => 'Island Transfer',
                                    'Transport' => 'Transport',
                                    'TransportUnit' => 'Transport',
                                    'TouristAttraction' => 'Tourist Attraction',
                                    'HotelRoom' => 'Hotel',
                                ];
                                $typeLabel = $typeLabelMap[$serviceableType] ?? $serviceableType;
                                $itemName = trim((string) ($item->serviceable?->name ?? ''));
                                if ($itemName === '' && $serviceableType === 'HotelRoom') {
                                    $itemName = trim((string) ($item->serviceable?->rooms ?? ''));
                                }
                                $descriptionLabel = $itemName !== '' ? $itemName : '-';
                                $vendorProviderItemLabel = $item->serviceable?->name ?? '-';
                                $dayNumber = (int) ($item->day_number ?? 0);
                                $dayKey = $dayNumber > 0 ? 'day-' . $dayNumber : 'day-without';
                                $dayText = $dayNumber > 0 ? ('Day ' . $dayNumber) : 'Without Day';

                                if (in_array($serviceableType, ['Activity', 'FoodBeverage', 'IslandTransfer', 'Transport', 'TransportUnit'], true)) {
                                    $vendorName = trim((string) ($item->serviceable?->vendor?->name ?? ''));
                                    if ($vendorName !== '') {
                                        $vendorProviderItemLabel = $vendorName;
                                    }
                                } elseif ($serviceableType === 'HotelRoom') {
                                    $hotelName = trim((string) ($item->serviceable?->hotel?->name ?? ''));
                                    if ($hotelName !== '') {
                                        $vendorProviderItemLabel = $hotelName;
                                    }

                                    $roomName = trim((string) ($item->serviceable?->rooms ?? $item->serviceable?->name ?? ''));
                                    if ($roomName !== '') {
                                        $descriptionLabel = $roomName;
                                    }
                                }

                                if ($serviceableType === 'FoodBeverage') {
                                    $serviceableMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                    $mealLabels = $resolveMealLabelsFromMeta($serviceableMeta);
                                    if ($mealLabels === []) {
                                        $startTime = trim((string) ($serviceableMeta['start_time'] ?? ''));
                                        if (preg_match('/^(\d{1,2}):(\d{2})$/', $startTime, $matches)) {
                                            $hour = (int) $matches[1];
                                            if ($hour <= 10) {
                                                $mealLabels = ['Breakfast'];
                                            } elseif ($hour <= 15) {
                                                $mealLabels = ['Lunch'];
                                            } else {
                                                $mealLabels = ['Dinner'];
                                            }
                                        }
                                    }
                                    if ($mealLabels !== []) {
                                        $descriptionLabel .= ' (' . implode(', ', $mealLabels) . ')';
                                    }
                                }
                            @endphp
                            <tr data-validation-item-row="{{ $item->id }}">
                                <td class="px-3 py-2 align-top">
                                    <div class="space-y-1">
                                        <input type="hidden" name="items[{{ $item->id }}][is_validated]" value="0">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                            <input type="checkbox" name="items[{{ $item->id }}][is_validated]" value="1" data-item-validated-checkbox="{{ $item->id }}" @checked((bool) ($item->is_validated ?? false)) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </label>
                                        <p data-item-feedback="{{ $item->id }}" class="hidden text-[11px]"></p>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-200">{{ $typeLabel }}</td>
                                <td class="px-3 py-2 align-top">
                                    <button type="button" data-open-validation-detail="{{ $item->id }}" class="text-left text-indigo-700 hover:underline dark:text-indigo-300">
                                        {{ $vendorProviderItemLabel }}
                                    </button>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <span class="text-gray-700 dark:text-gray-200">{{ $descriptionLabel }}</span>
                                </td>
                                <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-200">{{ (int) ($item->qty ?? 0) }}</td>
                                <td class="px-3 py-2 align-top">
                                    <div class="input-with-left-affix">
                                        <input
                                            type="text"
                                            pattern="[0-9.]*"
                                            inputmode="numeric"
                                            name="items[{{ $item->id }}][contract_rate]"
                                            value="{{ old('items.' . $item->id . '.contract_rate', number_format((float) ($item->contract_rate ?? 0), 0, ',', '.')) }}"
                                            data-contract-rate-input
                                            data-canonical-input="contract_rate-{{ $item->id }}"
                                            class="app-input pl-14 text-right"
                                        >
                                        <span
                                            data-contract-rate-badge="{{ $item->id }}"
                                            class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                        ></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <select name="items[{{ $item->id }}][markup_type]" data-canonical-input="markup_type-{{ $item->id }}" class="app-input">
                                        <option value="fixed" @selected(old('items.' . $item->id . '.markup_type', $item->markup_type) === 'fixed')>{{ __('ui.common.fixed') }}</option>
                                        <option value="percent" @selected(old('items.' . $item->id . '.markup_type', $item->markup_type) === 'percent')>{{ __('ui.common.percent') }}</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <div class="input-with-left-affix">
                                        <input
                                            type="text"
                                            pattern="[0-9.]*"
                                            inputmode="numeric"
                                            name="items[{{ $item->id }}][markup]"
                                            value="{{ old('items.' . $item->id . '.markup', number_format((float) ($item->markup ?? 0), 0, ',', '.')) }}"
                                            data-markup-input
                                            data-canonical-input="markup-{{ $item->id }}"
                                            class="app-input pl-14 text-right"
                                        >
                                        <span
                                            data-markup-badge="{{ $item->id }}"
                                            class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                        ></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-top text-xs text-gray-600 dark:text-gray-300" data-item-updated="{{ $item->id }}">
                                    {{ $item->validator?->name ?? '-' }}
                                    @if ($item->updated_at)
                                        <br><x-local-time :value="$item->updated_at" />
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-top" data-item-status="{{ $item->id }}">
                                    @if ((bool) ($item->is_validated ?? false))
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('ui.modules.quotations.validated') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ __('ui.modules.quotations.pending_validation') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <button type="button" class="btn-outline-sm" data-save-item="{{ $item->id }}" data-save-item-url="{{ route('quotations.validate.save-item', ['quotation' => $quotation, 'item' => $item]) }}">
                                        <span data-item-spinner="{{ $item->id }}" class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                                        <span data-item-save-label="{{ $item->id }}">{{ __('ui.modules.quotations.validate') }}</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                        @else
                            <tr>
                                <td colspan="11" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.no_validation_required_items') }}</td>
                            </tr>
                        @endif

                    </tbody>
                </table>
            </div>
            </div>

            <div class="module-action-row pt-2">
                <button type="submit" class="btn-secondary" data-save-progress-btn>
                    <span data-save-progress-spinner class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                    <span data-save-progress-label>{{ __('ui.modules.quotations.save_progress') }}</span>
                </button>
                <button
                    type="button"
                    class="btn-primary {{ (bool) ($progress['is_complete'] ?? false) ? '' : 'hidden' }}"
                    data-finalize-quotation-btn
                >
                    <span>{{ __('ui.modules.quotations.validate_quotation') }}</span>
                </button>
            </div>
        </form>
        <form id="quotation-finalize-form" method="POST" action="{{ route('quotations.validate.finalize', $quotation) }}" class="hidden">
            @csrf
        </form>
    </div>

    <div
        id="validation-item-detail-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
        data-detail-endpoint-template="{{ route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => '__ITEM__']) }}"
        data-contact-update-endpoint-template="{{ route('quotations.validate.update-item-contact', ['quotation' => $quotation, 'item' => '__ITEM__']) }}"
    >
        <div class="w-full max-w-3xl rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100" data-modal-title>{{ __('ui.modules.quotations.item_detail_modal_title') }}</h3>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-secondary px-2 py-1 text-xs" data-update-contact>
                        <span data-update-contact-spinner class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                        <span data-update-contact-label>{{ __('ui.modules.quotations.update_contact') }}</span>
                    </button>
                    <button type="button" class="btn-ghost px-2 py-1 text-xs" data-close-validation-modal>{{ __('ui.common.close') }}</button>
                </div>
            </div>

            <div data-modal-loading class="mt-6 hidden flex justify-center">
                <span class="inline-block h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-indigo-500 dark:border-gray-600 dark:border-t-indigo-300"></span>
            </div>
            <div data-modal-error class="mt-4 hidden rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300"></div>
            <div data-update-contact-feedback class="mt-4 hidden text-xs"></div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div data-modal-contact class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700">
                    <div class="mb-2">
                        <p class="font-semibold text-gray-900 dark:text-gray-100" data-contact-provider>-</p>
                        <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-300">
                            <span class="font-semibold">{{ __('ui.modules.quotations.address') }}:</span>
                            <span data-contact-address-display>-</span>
                        </p>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.contact_person') }}</label>
                            <input type="text" class="app-input mt-1 text-xs" data-contact-name>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.common.phone') }}</label>
                            <input type="text" class="app-input mt-1 text-xs" data-contact-phone>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.email') }}</label>
                            <input type="email" class="app-input mt-1 text-xs" data-contact-email>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ __('ui.modules.quotations.website') }}</label>
                            <input type="text" class="app-input mt-1 text-xs" data-contact-website>
                        </div>
                    </div>
                </div>
                <div data-modal-current class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700"></div>
            </div>

        </div>
    </div>
@endsection
