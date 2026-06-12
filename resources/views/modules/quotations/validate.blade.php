@extends('layouts.master')

@section('page_title', ui_phrase('Quotation Validation'))
@section('page_subtitle', ui_phrase('Review and approve quotations with accurate pricing, services, and validation records.'))
@section('page_actions')
    <x-quotation-action-button :href="route('quotations.show', $quotation)" variant="outline" icon="fa-eye" label="{{ ui_phrase('View Detail') }}" />
    @can('update', $quotation)
        <x-quotation-action-button :href="route('quotations.edit', $quotation)" variant="primary" icon="fa-pen-nib" label="{{ ui_phrase('Revise Quotation') }}" />
    @endcan
    <x-quotation-action-button :href="route('quotations.index')" variant="ghost" icon="fa-arrow-left" label="{{ ui_phrase('Back') }}" data-page-back-action />
@endsection

@push('scripts')
    <script>
        const initQuotationValidationDetailModal = (attempt = 0) => {
            const modal = document.getElementById('validation-item-detail-modal-content');
            if (!modal) {
                if (attempt < 40) {
                    window.setTimeout(() => {
                        initQuotationValidationDetailModal(attempt + 1);
                    }, 75);
                }
                return;
            }

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
            const communicationNoteInput = modal.querySelector('[data-validation-note]');
            const contactAddressDisplay = modal.querySelector('[data-contact-address-display]');
            const historyContainer = modal.querySelector('[data-validation-history]');
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
            const validationDetailModalName = 'validation-item-detail-modal';
            const i18n = {
                day: @json(ui_phrase('Day')),
                withoutDay: @json(ui_phrase('Without Day')),
                foodAndBeverage: @json(ui_phrase('Food and Beverage')),
                islandTransfer: @json(ui_phrase('Island Transfer')),
                touristAttraction: @json(ui_phrase('Tourist Attraction')),
                transport: @json(ui_phrase('Transport')),
                fixed: @json(ui_phrase('Fixed')),
                percent: @json(ui_phrase('Percent')),
                updatedBy: @json(ui_phrase('Updated by')),
                updatedAt: @json(ui_phrase('Updated at')),
                loadDetailFailed: @json(ui_phrase('load detail failed')),
                contactUpdateFailed: @json(ui_phrase('Failed to update contact details.')),
                contactUpdateSuccess: @json(ui_phrase('Contact details updated.')),
                saveItemFailed: @json(ui_phrase('Failed to save item validation.')),
                noHistory: @json(ui_phrase('No communication history yet.')),
            };

            const openModal = () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: validationDetailModalName }));
            };

            const closeModal = () => {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: validationDetailModalName }));
            };

            const readableItemType = (rawType) => {
                const type = String(rawType || '').trim();
                const map = {
                    FoodBeverage: i18n.foodAndBeverage,
                    IslandTransfer: i18n.islandTransfer,
                    TouristAttraction: i18n.touristAttraction,
                    TransportUnit: i18n.transport,
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
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const renderValidationHistory = (logs) => {
                if (!historyContainer) return;
                const items = (Array.isArray(logs) ? logs : [])
                    .slice()
                    .sort((a, b) => {
                        const aTime = new Date(String(a?.created_at || '')).getTime();
                        const bTime = new Date(String(b?.created_at || '')).getTime();
                        return (Number.isFinite(bTime) ? bTime : 0) - (Number.isFinite(aTime) ? aTime : 0);
                    })
                    .slice(0, 3);
                const formatHistoryTime = (isoDate) => {
                    if (!isoDate) return '-';
                    const parsed = new Date(isoDate);
                    if (Number.isNaN(parsed.getTime())) return '-';
                    const yyyy = parsed.getFullYear();
                    const mm = String(parsed.getMonth() + 1).padStart(2, '0');
                    const dd = String(parsed.getDate()).padStart(2, '0');
                    const hh = String(parsed.getHours()).padStart(2, '0');
                    const ii = String(parsed.getMinutes()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd} (${hh}:${ii})`;
                };
                if (!items.length) {
                    historyContainer.innerHTML = `<p class="text-[11px] text-gray-500 dark:text-gray-400">${escapeHtml(i18n.noHistory)}</p>`;
                    return;
                }
                historyContainer.innerHTML = items.map((entry) => {
                    const action = String(entry?.action || '').trim() || '-';
                    const actionScope = String(entry?.scope || '').trim() === 'service_item'
                        ? '{{ ui_phrase('Service Item') }}'
                        : '{{ ui_phrase('Quotation Item') }}';
                    const validator = String(entry?.validator_name || '-').trim() || '-';
                    const createdAt = formatHistoryTime(entry?.created_at || '');
                    const note = String(entry?.validation_notes || '').trim();
                    const noteDisplay = note !== '' ? escapeHtml(note) : '-';
                    return `
                        <div class="rounded-md border border-gray-200 px-2 py-1.5 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">${escapeHtml(action)} (${escapeHtml(actionScope)})</span>
                                <span class="text-gray-500 dark:text-gray-400">${escapeHtml(createdAt)}</span>
                            </div>
                            <p class="mt-0.5 text-gray-600 dark:text-gray-300">${escapeHtml(validator)}</p>
                            <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-300">${noteDisplay}</p>
                        </div>
                    `;
                }).join('');
            };

            const renderDetail = (payload) => {
                const item = payload.item || {};
                const contact = payload.contact || {};
                const serviceHistoryLogs = payload.service_history || [];

                if (titleEl) {
                    const dayNumber = Number(item.day_number || 0);
                    const dayLabel = dayNumber > 0 ? `${i18n.day} ${dayNumber}` : i18n.withoutDay;
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
                if (communicationNoteInput) {
                    const noteInput = getValidationNoteInput(item.id);
                    communicationNoteInput.value = noteInput?.value || '';
                }
                renderValidationHistory(serviceHistoryLogs);

                if (currentEl) {
                    const normalizedMarkupType = String(item.markup_type || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                    const markupDisplay = normalizedMarkupType === 'percent'
                        ? `${Number(item.markup || 0).toLocaleString(appDisplayLocale, { maximumFractionDigits: 2 })}%`
                        : formatMoneyFromIdr(item.markup);
                    const markupTypeLabel = normalizedMarkupType === 'percent' ? i18n.percent : i18n.fixed;
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
                        <div><span class="font-semibold">{{ ui_phrase('Active Contract Rate') }}:</span> ${formatMoneyFromIdr(item.contract_rate)}</div>
                        <div class="mt-1"><span class="font-semibold">{{ ui_phrase('Active Markup Type') }}:</span> ${markupTypeLabel}</div>
                        <div class="mt-1"><span class="font-semibold">{{ ui_phrase('Active Markup') }}:</span> ${markupDisplay}</div>
                        <div class="mt-1"><span class="font-semibold">${i18n.updatedBy}:</span> ${item.validator || '-'}</div>
                        <div class="mt-1"><span class="font-semibold">${i18n.updatedAt}:</span> ${updatedAtText}</div>
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
                        errorEl.textContent = i18n.loadDetailFailed;
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

            const setUpdateContactLoading = (isLoading) => {
                if (updateContactButton) {
                    updateContactButton.disabled = isLoading;
                }
                if (updateContactSpinner) {
                    updateContactSpinner.classList.toggle('hidden', !isLoading);
                }
                if (updateContactLabel) {
                    updateContactLabel.textContent = '{{ ui_phrase('Update') }}';
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
                        throw new Error(firstError || result?.message || i18n.contactUpdateFailed);
                    }

                    const contact = result?.contact || {};
                    if (contactNameInput) contactNameInput.value = contact.contact_name && contact.contact_name !== '-' ? contact.contact_name : '';
                    if (contactPhoneInput) contactPhoneInput.value = contact.contact_phone && contact.contact_phone !== '-' ? contact.contact_phone : '';
                    if (contactEmailInput) contactEmailInput.value = contact.contact_email && contact.contact_email !== '-' ? contact.contact_email : '';
                    if (contactWebsiteInput) contactWebsiteInput.value = contact.contact_website && contact.contact_website !== '-' ? contact.contact_website : '';
                    if (contactAddressDisplay) contactAddressDisplay.textContent = contact.contact_address || '-';
                    renderValidationHistory(result?.service_history || []);

                    setUpdateContactFeedback(result?.message || i18n.contactUpdateSuccess);
                } catch (error) {
                    setUpdateContactFeedback(error.message || i18n.contactUpdateFailed, 'error');
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
            const getValidationNoteInput = (itemId) => {
                if (!itemId) return null;
                return getCanonicalInput(itemId, 'validation_notes');
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
                setTextForSelectors('[data-confirm-contract-rate-badge]', currencyBadgeText);
                setTextForSelectors('[data-confirm-total-price-badge]', currencyBadgeText);
                document.querySelectorAll('[data-canonical-input^="markup_type-"]').forEach((input) => {
                    const itemId = String(input.getAttribute('data-canonical-input') || '').replace('markup_type-', '');
                    if (itemId !== '') {
                        refreshMarkupBadgesForItem(itemId);
                    }
                });
                syncConfirmMarkupBadge();
            };
            const hydrateValidationDisplaysFromCanonical = () => {
                document.querySelectorAll('[data-validation-item-row]').forEach((row) => {
                    const itemId = String(row.getAttribute('data-validation-item-row') || '').trim();
                    if (!itemId) return;

                    const canonicalRateInput = getCanonicalInput(itemId, 'contract_rate');
                    const canonicalMarkupTypeInput = getCanonicalInput(itemId, 'markup_type');
                    const canonicalMarkupInput = getCanonicalInput(itemId, 'markup');
                    const displayRate = Math.max(0, parseIntegerFromDisplay(canonicalRateInput?.value || 0));
                    const displayMarkup = Math.max(0, parseIntegerFromDisplay(canonicalMarkupInput?.value || 0));
                    const markupType = String(canonicalMarkupTypeInput?.value || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                    const publicRateIdr = markupType === 'percent'
                        ? toIdrInteger(displayRate + ((displayRate * displayMarkup) / 100))
                        : toIdrInteger(displayRate + displayMarkup);

                    document.querySelectorAll(`[data-contract-rate-display="${itemId}"], [data-mobile-contract-rate-display="${itemId}"]`).forEach((el) => {
                        el.textContent = formatMoneyFromIdr(toIdrInteger(displayRate));
                    });
                    document.querySelectorAll(`[data-public-rate-display="${itemId}"], [data-mobile-public-rate-display="${itemId}"]`).forEach((el) => {
                        el.textContent = formatMoneyFromIdr(publicRateIdr);
                    });
                    document.querySelectorAll(`[data-markup-type-display="${itemId}"], [data-mobile-markup-type-display="${itemId}"]`).forEach((el) => {
                        el.textContent = markupType === 'percent' ? '{{ ui_phrase('Percent') }}' : '{{ ui_phrase('Fixed') }}';
                    });
                    document.querySelectorAll(`[data-markup-display="${itemId}"], [data-mobile-markup-display="${itemId}"]`).forEach((el) => {
                        if (markupType === 'percent') {
                            el.textContent = `${Number(displayMarkup || 0).toLocaleString(appDisplayLocale, { maximumFractionDigits: 2 })}%`;
                        } else {
                            el.textContent = formatMoneyFromIdr(toIdrInteger(displayMarkup));
                        }
                    });
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
            const applyValidateButtonState = (itemId, isValidated) => {
                document.querySelectorAll(`[data-save-item="${itemId}"]`).forEach((btnEl) => {
                    btnEl.classList.remove('btn-primary-sm', 'btn-outline-sm');
                    btnEl.classList.add(isValidated ? 'btn-outline-sm' : 'btn-primary-sm');
                    btnEl.setAttribute('data-action-label', isValidated ? '{{ ui_phrase('Revalidate') }}' : '{{ ui_phrase('Validate') }}');
                    const labelEl = btnEl.querySelector(`[data-item-save-label="${itemId}"]`);
                    if (labelEl) {
                        labelEl.textContent = isValidated ? '{{ ui_phrase('Revalidate') }}' : '{{ ui_phrase('Validate') }}';
                    }
                });
            };

            const validateConfirmModalName = 'validate-item-confirm-modal';
            const validateConfirmModal = document.getElementById('validate-item-confirm-modal-content');
            const confirmContractRateInput = validateConfirmModal?.querySelector('[data-confirm-contract-rate-input]') || null;
            const confirmMarkupTypeInput = validateConfirmModal?.querySelector('[data-confirm-markup-type-input]') || null;
            const confirmMarkupInput = validateConfirmModal?.querySelector('[data-confirm-markup-input]') || null;
            const confirmQtyInput = validateConfirmModal?.querySelector('[data-confirm-qty-input]') || null;
            const confirmTotalPriceInput = validateConfirmModal?.querySelector('[data-confirm-total-price-input]') || null;
            const confirmValidationNoteInput = validateConfirmModal?.querySelector('[data-confirm-validation-note]') || null;
            const confirmMarkupBadge = validateConfirmModal?.querySelector('[data-confirm-markup-badge]') || null;
            const confirmValidateButton = validateConfirmModal?.querySelector('[data-confirm-validate-btn]') || null;
            const confirmModalTitle = validateConfirmModal?.querySelector('[data-confirm-modal-title]') || null;
            let pendingSaveButton = null;
            const syncConfirmMarkupBadge = () => {
                if (!confirmMarkupBadge) return;
                const markupType = String(confirmMarkupTypeInput?.value || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                confirmMarkupBadge.textContent = markupType === 'percent' ? '%' : currencyBadgeText;
            };
            const syncConfirmTotalPrice = () => {
                if (!confirmTotalPriceInput) return;
                const qty = Math.max(1, Number.parseInt(String(confirmQtyInput?.value || '1'), 10) || 1);
                const contractRate = Math.max(0, parseIntegerFromDisplay(confirmContractRateInput?.value || 0));
                const markupType = String(confirmMarkupTypeInput?.value || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                const markupValue = Math.max(0, parseIntegerFromDisplay(confirmMarkupInput?.value || 0));
                const unitPrice = markupType === 'percent'
                    ? (contractRate + ((contractRate * markupValue) / 100))
                    : (contractRate + markupValue);
                const totalPrice = Math.max(0, Math.round(qty * unitPrice));
                confirmTotalPriceInput.value = formatIntegerDisplay(totalPrice);
            };
            const syncLatestValidationCardVisibility = () => {
                const card = validateConfirmModal?.querySelector('[data-confirm-latest-card]');
                if (!card) return;
                const byField = validateConfirmModal?.querySelector('[data-confirm-field="last-validation-by"]');
                const atField = validateConfirmModal?.querySelector('[data-confirm-field="last-validation-at"]');
                const noteField = validateConfirmModal?.querySelector('[data-confirm-field="last-validation-note"]');
                const hasAnyData = Boolean(byField && !byField.classList.contains('hidden'))
                    || Boolean(atField && !atField.classList.contains('hidden'))
                    || Boolean(noteField && !noteField.classList.contains('hidden'));
                card.classList.toggle('hidden', !hasAnyData);
            };
            const normalizeModalValue = (value) => {
                const text = String(value ?? '').trim();
                if (text === '' || text === '-') return '';
                return text;
            };
            const setConfirmFieldValue = (fieldSelector, value) => {
                const field = validateConfirmModal?.querySelector(`[data-confirm-field="${fieldSelector}"]`);
                const target = validateConfirmModal?.querySelector(`[data-confirm-${fieldSelector}]`);
                if (!field || !target) return;
                const normalized = normalizeModalValue(value);
                if (normalized === '') {
                    field.classList.add('hidden');
                    target.textContent = '';
                    return;
                }
                field.classList.remove('hidden');
                target.textContent = normalized;
            };
            const hydrateConfirmLatestFromServiceHistory = async (itemId) => {
                if (!itemId || !endpointTemplate) return;
                const endpoint = endpointTemplate.replace('__ITEM__', String(itemId));
                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    const logs = Array.isArray(payload?.service_history) ? payload.service_history : [];
                    const latest = logs.length > 0 ? logs[0] : null;
                    const latestBy = String(latest?.validator_name || '').trim();
                    const latestAt = String(latest?.created_at || '').trim();
                    const latestNote = String(latest?.validation_notes || '').trim();

                    setConfirmFieldValue('last-validation-by', latestBy);
                    setConfirmFieldValue('last-validation-at', latestAt !== '' ? formatDateTime(latestAt) : '');
                    setConfirmFieldValue('last-validation-note', latestNote);
                    syncLatestValidationCardVisibility();

                    if (confirmModalTitle) {
                        const hasServiceValidation = latestBy !== '' || latestAt !== '' || latestNote !== '';
                        confirmModalTitle.textContent = hasServiceValidation
                            ? '{{ ui_phrase('Revalidate Item') }}'
                            : '{{ ui_phrase('Confirm Validation') }}';
                    }
                } catch (e) {
                    // Keep fallback values from current quotation item state.
                }
            };

            const openValidateConfirmModal = (button) => {
                if (!button || !validateConfirmModal) return false;

                const itemId = String(button.getAttribute('data-save-item') || '').trim();
                if (itemId !== '') {
                    syncMobileFieldsToCanonical(itemId);
                }

                const itemType = String(button.getAttribute('data-item-type') || '-').trim() || '-';
                const itemCategory = String(button.getAttribute('data-item-category') || '').trim().toLowerCase();
                const itemBrand = String(button.getAttribute('data-item-brand') || '-').trim() || '-';
                const itemName = String(button.getAttribute('data-item-name') || '-').trim() || '-';
                const vendorName = String(button.getAttribute('data-vendor-name') || '-').trim() || '-';
                const contactPerson = String(button.getAttribute('data-contact-person') || '-').trim() || '-';
                const contactPhone = String(button.getAttribute('data-contact-phone') || '-').trim() || '-';
                const contactEmail = String(button.getAttribute('data-contact-email') || '-').trim() || '-';
                const contactWebsite = String(button.getAttribute('data-contact-website') || '-').trim() || '-';
                const itemPax = String(button.getAttribute('data-item-pax') || '-').trim() || '-';
                const itemServiceDate = String(button.getAttribute('data-item-service-date') || '-').trim() || '-';
                const itemQtyRaw = String(button.getAttribute('data-item-qty') || '').trim();
                const lastValidationBy = String(button.getAttribute('data-last-validation-by') || '').trim();
                const lastValidationAtRaw = String(button.getAttribute('data-last-validation-at') || '').trim();
                const lastValidationNote = String(button.getAttribute('data-last-validation-note') || '').trim();
                const contractRateInput = itemId ? getCanonicalInput(itemId, 'contract_rate') : null;
                const markupTypeInput = itemId ? getCanonicalInput(itemId, 'markup_type') : null;
                const markupInput = itemId ? getCanonicalInput(itemId, 'markup') : null;
                const validationNoteInput = itemId ? getValidationNoteInput(itemId) : null;
                const contractRateDisplay = Math.max(0, parseIntegerFromDisplay(contractRateInput?.value || 0));
                const markupDisplay = Math.max(0, parseIntegerFromDisplay(markupInput?.value || 0));
                const markupType = String(markupTypeInput?.value || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';

                const normalizedBrand = normalizeModalValue(itemBrand);
                const normalizedName = normalizeModalValue(itemName);
                const combinedItemName = normalizedBrand !== '' ? `${normalizedBrand} ${normalizedName}`.trim() : normalizedName;
                const normalizedVendor = normalizeModalValue(vendorName);
                const vendorOwnedCategories = ['foodbeverage', 'transport', 'transportunit', 'activity', 'islandtransfer'];
                const isVendorOwnedItem = vendorOwnedCategories.includes(itemCategory);
                const isDuplicateVendorName = normalizedVendor !== ''
                    && combinedItemName !== ''
                    && normalizedVendor.toLowerCase() === combinedItemName.toLowerCase();

                setConfirmFieldValue('item-type', itemType);
                setConfirmFieldValue('item-name', combinedItemName);
                setConfirmFieldValue('vendor-name', (!isVendorOwnedItem && isDuplicateVendorName) ? '' : vendorName);
                setConfirmFieldValue('contact-person', contactPerson);
                setConfirmFieldValue('contact-phone', contactPhone);
                setConfirmFieldValue('contact-email', contactEmail);
                setConfirmFieldValue('contact-website', contactWebsite);
                setConfirmFieldValue('item-pax', itemPax);
                setConfirmFieldValue('item-service-date', itemServiceDate);
                setConfirmFieldValue('last-validation-by', lastValidationBy);
                setConfirmFieldValue('last-validation-at', lastValidationAtRaw !== '' ? formatDateTime(lastValidationAtRaw) : '');
                setConfirmFieldValue('last-validation-note', lastValidationNote);
                syncLatestValidationCardVisibility();
                if (confirmContractRateInput) confirmContractRateInput.value = formatIntegerDisplay(contractRateDisplay);
                if (confirmMarkupTypeInput) confirmMarkupTypeInput.value = markupType;
                if (confirmMarkupInput) confirmMarkupInput.value = formatIntegerDisplay(markupDisplay);
                if (confirmQtyInput) {
                    const normalizedQty = Math.max(1, Number.parseInt(itemQtyRaw || '1', 10) || 1);
                    confirmQtyInput.value = String(normalizedQty);
                }
                if (confirmValidationNoteInput) confirmValidationNoteInput.value = String(validationNoteInput?.value || '');
                syncConfirmMarkupBadge();
                syncConfirmTotalPrice();
                if (confirmValidateButton) {
                    const actionLabel = String(button.getAttribute('data-action-label') || '').trim();
                    confirmValidateButton.textContent = actionLabel !== '' ? actionLabel : '{{ ui_phrase('Validate') }}';
                }
                if (confirmModalTitle) {
                    const hasValidatedHistory = lastValidationBy !== '' || lastValidationAtRaw !== '' || lastValidationNote !== '';
                    confirmModalTitle.textContent = hasValidatedHistory
                        ? '{{ ui_phrase('Revalidate Item') }}'
                        : '{{ ui_phrase('Confirm Validation') }}';
                }

                validateConfirmModal.setAttribute('data-current-item-id', itemId);
                refreshMarkupBadgesForItem(itemId);
                pendingSaveButton = button;
                window.dispatchEvent(new CustomEvent('open-modal', { detail: validateConfirmModalName }));
                hydrateConfirmLatestFromServiceHistory(itemId);
                return true;
            };

            const saveItemAjax = async (button) => {
                const itemId = button.getAttribute('data-save-item');
                const url = button.getAttribute('data-save-item-url');
                if (!itemId || !url) return;

                syncMobileFieldsToCanonical(itemId);

                const contractRateInput = getCanonicalInput(itemId, 'contract_rate');
                const markupTypeInput = getCanonicalInput(itemId, 'markup_type');
                const markupInput = getCanonicalInput(itemId, 'markup');
                const qtyInput = getCanonicalInput(itemId, 'qty');
                const validationNoteInput = getValidationNoteInput(itemId);
                const normalizedContractRateDisplay = Math.max(0, parseIntegerFromDisplay(contractRateInput?.value || 0));
                const normalizedMarkupDisplay = Math.max(0, parseIntegerFromDisplay(markupInput?.value || 0));
                const normalizedQty = Math.max(1, Number.parseInt(String(qtyInput?.value || '1'), 10) || 1);
                const normalizedContractRate = toIdrInteger(normalizedContractRateDisplay);
                const normalizedMarkup = toIdrInteger(normalizedMarkupDisplay);
                const validationNote = String(validationNoteInput?.value || '').trim();
                if (contractRateInput) {
                    contractRateInput.value = formatIntegerDisplay(normalizedContractRateDisplay);
                }
                if (markupInput) {
                    markupInput.value = formatIntegerDisplay(normalizedMarkupDisplay);
                }
                if (qtyInput) {
                    qtyInput.value = String(normalizedQty);
                }

                const payload = {
                    _method: 'PATCH',
                    qty: normalizedQty,
                    contract_rate: normalizedContractRate,
                    markup_type: markupTypeInput?.value ?? 'fixed',
                    markup: normalizedMarkup,
                    validation_notes: validationNote,
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
                        throw new Error(firstError || result?.message || i18n.saveItemFailed);
                    }

                    updateProgressUi(result.progress || {});
                    const affectedItems = Array.isArray(result.items) && result.items.length > 0
                        ? result.items
                        : [result.item || {}];
                    affectedItems.forEach((affectedItem) => {
                        const affectedItemId = String(affectedItem?.id || '').trim();
                        if (affectedItemId !== '') {
                            updateItemRowUi(affectedItemId, affectedItem || {});
                        }
                    });
                    setItemFeedback(itemId, '');
                } catch (error) {
                    setItemFeedback(itemId, error.message || i18n.saveItemFailed, 'error');
                } finally {
                    setItemButtonLoading(itemId, false);
                }
            };

            confirmValidateButton?.addEventListener('click', async () => {
                if (!pendingSaveButton) return;
                const targetButton = pendingSaveButton;
                const itemId = String(targetButton.getAttribute('data-save-item') || '').trim();
                if (itemId !== '') {
                    const canonicalRateInput = getCanonicalInput(itemId, 'contract_rate');
                    const canonicalMarkupTypeInput = getCanonicalInput(itemId, 'markup_type');
                    const canonicalMarkupInput = getCanonicalInput(itemId, 'markup');
                    const canonicalQtyInput = getCanonicalInput(itemId, 'qty');
                    const canonicalValidationNoteInput = getValidationNoteInput(itemId);
                    if (canonicalRateInput && confirmContractRateInput) {
                        canonicalRateInput.value = formatIntegerDisplay(confirmContractRateInput.value);
                    }
                    if (canonicalMarkupTypeInput && confirmMarkupTypeInput) {
                        canonicalMarkupTypeInput.value = confirmMarkupTypeInput.value || 'fixed';
                    }
                    if (canonicalMarkupInput && confirmMarkupInput) {
                        canonicalMarkupInput.value = formatIntegerDisplay(confirmMarkupInput.value);
                    }
                    if (canonicalQtyInput && confirmQtyInput) {
                        canonicalQtyInput.value = String(Math.max(1, Number.parseInt(String(confirmQtyInput.value || '1'), 10) || 1));
                    }
                    if (canonicalValidationNoteInput && confirmValidationNoteInput) {
                        canonicalValidationNoteInput.value = String(confirmValidationNoteInput.value || '').trim();
                    }
                }
                pendingSaveButton = null;
                window.dispatchEvent(new CustomEvent('close-modal', { detail: validateConfirmModalName }));
                await saveItemAjax(targetButton);
            });

            window.addEventListener('close-modal', (event) => {
                if ((event?.detail || '') === validateConfirmModalName) {
                    pendingSaveButton = null;
                }
            });

            communicationNoteInput?.addEventListener('input', () => {
                const itemId = String(modal.getAttribute('data-current-item-id') || '').trim();
                if (!itemId) return;
                const noteInput = getValidationNoteInput(itemId);
                if (noteInput) {
                    noteInput.value = communicationNoteInput.value;
                }
            });

            document.querySelectorAll('[data-save-item]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    openValidateConfirmModal(btn);
                });
            });

            if (confirmContractRateInput) {
                confirmContractRateInput.addEventListener('input', () => {
                    confirmContractRateInput.value = formatIntegerDisplay(confirmContractRateInput.value);
                    syncConfirmTotalPrice();
                });
                confirmContractRateInput.addEventListener('blur', () => {
                    confirmContractRateInput.value = formatIntegerDisplay(confirmContractRateInput.value);
                    syncConfirmTotalPrice();
                });
            }
            if (confirmMarkupInput) {
                confirmMarkupInput.addEventListener('input', () => {
                    confirmMarkupInput.value = formatIntegerDisplay(confirmMarkupInput.value);
                    syncConfirmTotalPrice();
                });
                confirmMarkupInput.addEventListener('blur', () => {
                    confirmMarkupInput.value = formatIntegerDisplay(confirmMarkupInput.value);
                    syncConfirmTotalPrice();
                });
            }
            if (confirmQtyInput) {
                confirmQtyInput.addEventListener('input', () => {
                    const normalizedQty = Math.max(1, Number.parseInt(String(confirmQtyInput.value || '1'), 10) || 1);
                    confirmQtyInput.value = String(normalizedQty);
                    syncConfirmTotalPrice();
                });
                confirmQtyInput.addEventListener('blur', () => {
                    const normalizedQty = Math.max(1, Number.parseInt(String(confirmQtyInput.value || '1'), 10) || 1);
                    confirmQtyInput.value = String(normalizedQty);
                    syncConfirmTotalPrice();
                });
            }
            confirmMarkupTypeInput?.addEventListener('change', () => {
                syncConfirmMarkupBadge();
                syncConfirmTotalPrice();
            });

            document.querySelectorAll('[data-canonical-input^="markup-"]').forEach((input) => {
                convertInputFromIdrToDisplayCurrency(input);
            });

            document.querySelectorAll('[data-canonical-input^="contract_rate-"]').forEach((input) => {
                convertInputFromIdrToDisplayCurrency(input);
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
                document.querySelectorAll('[data-canonical-input^="contract_rate-"]').forEach((input) => {
                    const normalizedDisplay = Math.max(0, parseIntegerFromDisplay(input.value));
                    input.value = String(toIdrInteger(normalizedDisplay));
                });
                document.querySelectorAll('[data-canonical-input^="markup-"]').forEach((input) => {
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
                const progressLineFillEl = document.querySelector('[data-progress-line-fill]');
                const progressLineDotEl = document.querySelector('[data-progress-line-dot]');

                if (totalValidatedEl && progress.total_validated !== undefined) {
                    totalValidatedEl.textContent = String(progress.total_validated);
                }
                if (percentEl && progress.validation_percent !== undefined) {
                    percentEl.textContent = `${Number(progress.validation_percent || 0)}%`;
                }
                if (progress.validation_percent !== undefined) {
                    const normalizedPercent = Math.max(0, Math.min(100, Number(progress.validation_percent || 0)));
                    if (progressLineFillEl) {
                        progressLineFillEl.style.width = `${normalizedPercent}%`;
                    }
                    if (progressLineDotEl) {
                        progressLineDotEl.style.left = `calc(${normalizedPercent}% - 7px)`;
                    }
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
                const qtyDisplays = document.querySelectorAll(`[data-item-qty-display="${itemId}"], [data-mobile-item-qty-display="${itemId}"]`);
                const contractRateDisplays = document.querySelectorAll(`[data-contract-rate-display="${itemId}"], [data-mobile-contract-rate-display="${itemId}"]`);
                const publicRateDisplays = document.querySelectorAll(`[data-public-rate-display="${itemId}"], [data-mobile-public-rate-display="${itemId}"]`);
                const markupDisplays = document.querySelectorAll(`[data-markup-display="${itemId}"], [data-mobile-markup-display="${itemId}"]`);
                const markupTypeDisplays = document.querySelectorAll(`[data-markup-type-display="${itemId}"], [data-mobile-markup-type-display="${itemId}"]`);
                const canonicalMarkupTypeInput = getCanonicalInput(itemId, 'markup_type');
                const canonicalRateInput = getCanonicalInput(itemId, 'contract_rate');
                const canonicalMarkupInput = getCanonicalInput(itemId, 'markup');
                const canonicalQtyInput = getCanonicalInput(itemId, 'qty');

                statusCells.forEach((statusCell) => {
                    if (item.is_validated) {
                        statusCell.innerHTML = `<span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Validated') }}</span>`;
                    } else {
                        statusCell.innerHTML = `<span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ ui_phrase('Pending') }}</span>`;
                    }
                });

                updatedCells.forEach((updatedCell) => {
                    const validatorName = item.validator_name || '-';
                    const updatedAt = formatDateTime(item.updated_at);
                    const statusBadgeHtml = item.is_validated
                        ? `<span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ ui_phrase('Validated') }}</span>`
                        : `<span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">{{ ui_phrase('Pending') }}</span>`;
                    updatedCell.innerHTML = `${validatorName}<br>${updatedAt}<div class="mt-1">${statusBadgeHtml}</div>`;
                });

                validatedCheckboxes.forEach((validatedCheckbox) => {
                    validatedCheckbox.checked = Boolean(item.is_validated);
                });

                if (canonicalMarkupTypeInput && item.markup_type !== undefined) {
                    canonicalMarkupTypeInput.value = String(item.markup_type || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                }
                if (canonicalRateInput && item.contract_rate !== undefined) {
                    canonicalRateInput.value = formatIntegerDisplay(fromIdrToDisplayInteger(item.contract_rate));
                }
                if (canonicalMarkupInput && item.markup !== undefined) {
                    canonicalMarkupInput.value = formatIntegerDisplay(fromIdrToDisplayInteger(item.markup));
                }
                if (canonicalQtyInput && item.qty !== undefined) {
                    canonicalQtyInput.value = String(Math.max(1, Number.parseInt(String(item.qty || 1), 10) || 1));
                }
                qtyDisplays.forEach((el) => {
                    const qtyValue = Math.max(1, Number.parseInt(String(item.qty || canonicalQtyInput?.value || 1), 10) || 1);
                    el.textContent = String(qtyValue);
                });

                contractRateDisplays.forEach((el) => {
                    el.textContent = formatMoneyFromIdr(item.contract_rate || 0);
                });
                const nextMarkupType = String(item.markup_type || canonicalMarkupTypeInput?.value || 'fixed').toLowerCase() === 'percent' ? 'percent' : 'fixed';
                const nextContractRate = Math.max(0, Number(item.contract_rate || 0));
                const nextMarkupValue = Math.max(0, Number(item.markup || 0));
                const nextPublicRate = nextMarkupType === 'percent'
                    ? Math.round(nextContractRate + ((nextContractRate * nextMarkupValue) / 100))
                    : Math.round(nextContractRate + nextMarkupValue);
                publicRateDisplays.forEach((el) => {
                    el.textContent = formatMoneyFromIdr(nextPublicRate);
                });
                markupDisplays.forEach((el) => {
                    if (nextMarkupType === 'percent') {
                        el.textContent = `${Number(item.markup || 0).toLocaleString(appDisplayLocale, { maximumFractionDigits: 2 })}%`;
                    } else {
                        el.textContent = formatMoneyFromIdr(item.markup || 0);
                    }
                });
                markupTypeDisplays.forEach((el) => {
                    el.textContent = nextMarkupType === 'percent' ? '{{ ui_phrase('Percent') }}' : '{{ ui_phrase('Fixed') }}';
                });
                document.querySelectorAll(`[data-save-item="${itemId}"]`).forEach((btnEl) => {
                    const hasValidatedHistory = Boolean(item.is_validated);
                    if (item.qty !== undefined) {
                        btnEl.setAttribute('data-item-qty', String(Math.max(1, Number.parseInt(String(item.qty || 1), 10) || 1)));
                    }
                    btnEl.setAttribute('data-last-validation-by', hasValidatedHistory ? String(item.validator_name || '') : '');
                    btnEl.setAttribute('data-last-validation-at', hasValidatedHistory ? String(item.updated_at || '') : '');
                    btnEl.setAttribute('data-last-validation-note', hasValidatedHistory ? String(item.validation_notes || '') : '');
                });

                applyValidateButtonState(itemId, Boolean(item.is_validated));
                refreshMarkupBadgesForItem(itemId);
            };

            refreshAllMoneyBadges();
            hydrateValidationDisplaysFromCanonical();
        };
        initQuotationValidationDetailModal(0);
    </script>
@endpush

@section('content')
    @php
        $validationLocked = $quotation->isStatus(
            \App\Models\Quotation::STATUS_SENT,
            \App\Models\Quotation::STATUS_CUSTOMER_APPROVED,
            \App\Models\Quotation::STATUS_BOOKING_CREATED,
            \App\Models\Quotation::STATUS_IN_OPERATION,
            \App\Models\Quotation::STATUS_COMPLETED
        );
        $validationLockedStatus = \App\Support\Workflow\QuotationWorkflow::label((string) ($quotation->status ?? 'draft'));
        $validationLockedMessage = ui_phrase('Validation is locked because this quotation is currently :status. Continue from the relevant workflow action instead of editing validation data.', ['status' => $validationLockedStatus]);
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
            foreach (['breakfast' => ui_phrase('Breakfast'), 'lunch' => ui_phrase('Lunch'), 'dinner' => ui_phrase('Dinner')] as $key => $label) {
                if (in_array($key, $tokens, true)) {
                    $labels[] = $label;
                }
            }

            return $labels;
        };
        $resolveValidationContactData = static function ($item): array {
            $serviceable = $item->serviceable;
            $serviceableMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
            $contactOverride = is_array($serviceableMeta['validation_contact'] ?? null) ? $serviceableMeta['validation_contact'] : [];
            $serviceableType = class_basename((string) ($item->serviceable_type ?? ''));

            $vendorName = '-';
            $contactName = '-';
            $contactPhone = '-';
            $contactEmail = '-';
            $contactWebsite = '-';

            if (in_array($serviceableType, ['Activity', 'FoodBeverage', 'IslandTransfer', 'Transport', 'TransportUnit'], true)) {
                $vendor = $serviceable?->vendor;
                $vendorName = trim((string) ($vendor?->name ?? $serviceable?->name ?? '-')) ?: '-';
                $contactName = trim((string) ($vendor?->contact_name ?? $serviceable?->contact_name ?? '-')) ?: '-';
                $contactPhone = trim((string) ($vendor?->contact_phone ?? $serviceable?->contact_phone ?? '-')) ?: '-';
                $contactEmail = trim((string) ($vendor?->contact_email ?? $serviceable?->contact_email ?? '-')) ?: '-';
                $contactWebsite = trim((string) ($vendor?->website ?? $serviceable?->website ?? $serviceable?->web ?? '-')) ?: '-';
            } elseif ($serviceableType === 'HotelRoom') {
                $vendorName = trim((string) ($serviceable?->hotel?->name ?? $serviceable?->rooms ?? '-')) ?: '-';
                $contactName = trim((string) ($serviceable?->hotel?->contact_person ?? '-')) ?: '-';
                $contactPhone = trim((string) ($serviceable?->hotel?->phone ?? '-')) ?: '-';
                $contactWebsite = trim((string) ($serviceable?->hotel?->web ?? $serviceable?->hotel?->website ?? '-')) ?: '-';
            } elseif ($serviceableType === 'TouristAttraction') {
                $vendorName = trim((string) ($serviceable?->name ?? '-')) ?: '-';
                $contactWebsite = trim((string) ($serviceable?->google_maps_url ?? $serviceable?->website ?? $serviceable?->web ?? '-')) ?: '-';
            }

            $contactName = trim((string) ($contactOverride['contact_name'] ?? $contactName)) ?: '-';
            $contactPhone = trim((string) ($contactOverride['contact_phone'] ?? $contactPhone)) ?: '-';
            $contactEmail = trim((string) ($contactOverride['contact_email'] ?? $contactEmail)) ?: '-';
            $contactWebsite = trim((string) ($contactOverride['contact_website'] ?? $contactWebsite)) ?: '-';

            return [
                'vendor_name' => $vendorName,
                'contact_name' => $contactName,
                'contact_phone' => $contactPhone,
                'contact_email' => $contactEmail,
                'contact_website' => $contactWebsite,
            ];
        };
        $resolveItemBrandForValidation = static function ($item): string {
            $serviceableType = class_basename((string) ($item->serviceable_type ?? ''));
            if (! in_array($serviceableType, ['Transport', 'TransportUnit'], true)) {
                return '-';
            }
            $brand = trim((string) ($item->serviceable?->brand_model ?? ''));
            return $brand !== '' ? $brand : '-';
        };
        $resolveValidationDescriptionLabel = static function ($item): string {
            $serviceableType = class_basename((string) ($item->serviceable_type ?? ''));
            $rawDescription = trim((string) ($item->description ?? ''));
            $rawDescription = preg_replace('/^\s*Day\s+\d+\s*-\s*/i', '', $rawDescription) ?? $rawDescription;
            $rawDescription = preg_replace('/^\s*Without\s+Day\s*-\s*/i', '', $rawDescription) ?? $rawDescription;

            if (in_array($serviceableType, ['Activity', 'FoodBeverage'], true)) {
                $prefix = $serviceableType === 'Activity' ? 'Activity' : 'F&B';
                $description = preg_replace('/^\s*' . preg_quote($prefix, '/') . ':\s*/i', '', $rawDescription) ?? $rawDescription;
                $description = preg_replace_callback('/\((adult|child)\)/i', static function ($matches): string {
                    return '(' . strtoupper((string) ($matches[1] ?? '')) . ')';
                }, $description) ?? $description;
                $description = trim((string) preg_replace('/\s+/', ' ', $description));
                return $description !== '' ? $description : '-';
            }

            return $rawDescription !== '' ? $rawDescription : '-';
        };
    @endphp
    <div class="space-y-6 module-page module-page--quotations">
        @if ($validationLocked)
            <x-ui.lock-alert
                :title="ui_phrase('Validation Locked')"
                :message="$validationLockedMessage"
                type="warning"
            />
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

        <x-ui.section-card :title="ui_phrase('Validation Summary')">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Number') }}</p>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</h2>
                </div>
                <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                    <div>{{ ui_phrase('Status') }}: <x-ui.status-badge :status="$quotation->status" size="xs" /></div>
                    <div>{{ ui_phrase('Validation Status') }}: <x-ui.status-badge :status="$quotation->validation_status ?? 'pending'" size="xs" data-progress-status /></div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-5">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Items') }}</p>
                        <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-gray-100">{{ (int) ($progress['total_items'] ?? 0) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Required Validation') }}</p>
                        <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-gray-100">{{ (int) ($progress['total_required'] ?? 0) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Validated Items') }}</p>
                        <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-gray-100" data-progress-total-validated>{{ (int) ($progress['total_validated'] ?? 0) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Validation Progress') }}</p>
                        <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-gray-100" data-progress-percent>{{ (int) ($progress['validation_percent'] ?? 0) }}%</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer:') }}</p>
                        <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->inquiry?->customer?->name ?? '-' }}</p>
                    </div>
                </div>
                <div>
                    <div class="relative h-2.5 rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2.5 rounded-full bg-emerald-500 transition-all duration-300"
                            data-progress-line-fill
                            style="width: {{ max(0, min(100, (int) ($progress['validation_percent'] ?? 0))) }}%;"
                        ></div>
                        <span
                            class="absolute top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full border-2 border-white bg-emerald-600 shadow-sm transition-all duration-300 dark:border-gray-900"
                            data-progress-line-dot
                            style="left: calc({{ max(0, min(100, (int) ($progress['validation_percent'] ?? 0))) }}% - 7px);"
                        ></span>
                    </div>
                </div>
            </div>
        </x-ui.section-card>

        <form method="POST" action="{{ route('quotations.validate.save-progress', $quotation) }}" class="space-y-4" data-validation-progress-form>
            @csrf
            @method('PATCH')

            @php
                $paxAdult = max(0, (int) ($quotation->pax_adult ?? 0));
                $paxChild = max(0, (int) ($quotation->pax_child ?? 0));
                $paxLabel = ($paxAdult > 0 || $paxChild > 0)
                    ? trim(($paxAdult > 0 ? ($paxAdult . ' Adult') : '0 Adult') . ' / ' . ($paxChild > 0 ? ($paxChild . ' Child') : '0 Child'))
                    : '-';
                $serviceDate = $quotation->service_date;
                $quotationServiceDateBase = $serviceDate ? \Illuminate\Support\Carbon::parse($serviceDate)->startOfDay() : null;
            @endphp

            <x-ui.section-card :title="ui_phrase('Quotation Detail')">
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Order Number') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->order_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Service Date') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $serviceDate ? $serviceDate->format('Y-m-d') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Pax') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $paxLabel }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Validity Date') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</p>
                    </div>
                </div>
            </x-ui.section-card>
            

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
                            $dayText = $groupDayNumber > 0 ? (ui_phrase('Day') . ' ' . $groupDayNumber) : ui_phrase('Without Day');
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
                                            'Activity' => ui_phrase('Activity'),
                                            'FoodBeverage' => ui_phrase('Food and Beverage'),
                                            'IslandTransfer' => ui_phrase('Island Transfer'),
                                            'Transport' => ui_phrase('Transport'),
                                            'TransportUnit' => ui_phrase('Transport'),
                                            'TouristAttraction' => ui_phrase('Tourist Attraction'),
                                            'HotelRoom' => ui_phrase('Hotel'),
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

                                        if (in_array($serviceableType, ['Activity', 'FoodBeverage'], true)) {
                                            $descriptionLabel = $resolveValidationDescriptionLabel($item);
                                            $serviceableMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                            $mealLabels = $resolveMealLabelsFromMeta($serviceableMeta);
                                        }
                                    @endphp
                                    @php
                                        $contactData = $resolveValidationContactData($item);
                                        $itemBrand = $resolveItemBrandForValidation($item);
                                        $itemDayNumber = (int) ($item->day_number ?? 0);
                                        if ($itemDayNumber <= 0) {
                                            $itemMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                            $itemDayNumber = (int) ($itemMeta['day_number'] ?? 0);
                                        }
                                        $itemServiceDateLabel = '-';
                                        if ($quotationServiceDateBase) {
                                            $itemServiceDate = $quotationServiceDateBase->copy();
                                            if ($itemDayNumber > 1) {
                                                $itemServiceDate->addDays($itemDayNumber - 1);
                                            }
                                            $itemServiceDateLabel = $itemServiceDate->format('Y-m-d');
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
                                                <button
                                                    type="button"
                                                    class="{{ (bool) ($item->is_validated ?? false) ? 'btn-outline-sm' : 'btn-primary-sm' }}"
                                                    data-save-item="{{ $item->id }}"
                                                    data-save-item-url="{{ route('quotations.validate.save-item', ['quotation' => $quotation, 'item' => $item]) }}"
                                                    data-item-type="{{ $typeLabel }}"
                                                    data-item-category="{{ $serviceableType }}"
                                                    data-item-brand="{{ $itemBrand }}"
                                                    data-item-name="{{ $descriptionLabel }}"
                                                    data-vendor-name="{{ $contactData['vendor_name'] }}"
                                                    data-contact-person="{{ $contactData['contact_name'] }}"
                                                    data-contact-phone="{{ $contactData['contact_phone'] }}"
                                                    data-contact-email="{{ $contactData['contact_email'] }}"
                                                    data-contact-website="{{ $contactData['contact_website'] }}"
                                                    data-item-qty="{{ (int) ($item->qty ?? 1) }}"
                                                    data-item-pax="{{ $paxLabel }}"
                                                    data-item-service-date="{{ $itemServiceDateLabel }}"
                                                    data-last-validation-by="{{ (bool) ($item->is_validated ?? false) ? ($item->validator?->name ?? '') : '' }}"
                                                    data-last-validation-at="{{ (bool) ($item->is_validated ?? false) ? optional($item->updated_at)->toIso8601String() : '' }}"
                                                    data-last-validation-note="{{ (bool) ($item->is_validated ?? false) ? (string) ($item->validation_notes ?? '') : '' }}"
                                                    data-action-label="{{ (bool) ($item->is_validated ?? false) ? ui_phrase('Revalidate') : ui_phrase('Validate') }}"
                                                >
                                                    <span data-item-spinner="{{ $item->id }}" class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                                                    <span data-item-save-label="{{ $item->id }}">{{ (bool) ($item->is_validated ?? false) ? ui_phrase('Revalidate') : ui_phrase('Validate') }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                            <div>{{ ui_phrase('Description') }}</div>
                                            <div class="text-right text-gray-800 dark:text-gray-100">
                                                <div class="font-semibold">{{ $typeLabel }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $descriptionLabel }}</div>
                                            </div>
                                            <div>{{ ui_phrase('Qty') }}</div><div class="text-right text-gray-800 dark:text-gray-100" data-mobile-item-qty-display="{{ $item->id }}">{{ (int) ($item->qty ?? 0) }}</div>
                                            <div>{{ ui_phrase('Contract Rate') }}</div>
                                            <div class="text-right text-gray-800 dark:text-gray-100" data-mobile-contract-rate-display="{{ $item->id }}">
                                                -
                                            </div>
                                            <div>{{ ui_phrase('Markup Type') }}</div>
                                            <div class="text-right text-gray-800 dark:text-gray-100" data-mobile-markup-type-display="{{ $item->id }}">
                                                {{ (old('items.' . $item->id . '.markup_type', $item->markup_type) === 'percent') ? ui_phrase('Percent') : ui_phrase('Fixed') }}
                                            </div>
                                            <div>{{ ui_phrase('Markup') }}</div>
                                            <div class="text-right text-gray-800 dark:text-gray-100" data-mobile-markup-display="{{ $item->id }}">
                                                -
                                            </div>
                                            <div>{{ ui_phrase('Public Rate') }}</div>
                                            <div class="text-right text-gray-800 dark:text-gray-100" data-mobile-public-rate-display="{{ $item->id }}">
                                                -
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
                                                <x-ui.status-badge status="validated" :label="ui_phrase('Validated')" size="xs" />
                                            @else
                                                <x-ui.status-badge status="pending" :label="ui_phrase('Pending')" size="xs" />
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
                    <x-ui.empty-state :title="ui_phrase('No validation required items')" :description="ui_phrase('There are no items that require validation for this quotation.')" />
                @endif
            </div>

            <div class="responsive-data-desktop">
                <div class="app-card overflow-x-auto">
                <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="table-header">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Mark Validated') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Vendor/Provider/Item') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Description') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Contract Rate/pax') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Markup/pax') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Qty') }}</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Public Rate/pax') }}</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Validated By') }}</th>
                            {{-- <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Validation Status') }}</th> --}}
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @if ($groupedValidationItems->isNotEmpty())
                        @foreach ($groupedValidationItems as $dayKey => $dayItems)
                            @php
                                $firstDayItem = $dayItems->first();
                                $groupDayNumber = (int) ($firstDayItem->day_number ?? 0);
                                $dayText = $groupDayNumber > 0 ? (ui_phrase('Day') . ' ' . $groupDayNumber) : ui_phrase('Without Day');
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
                                    'Activity' => ui_phrase('Activity'),
                                    'FoodBeverage' => ui_phrase('Food and Beverage'),
                                    'IslandTransfer' => ui_phrase('Island Transfer'),
                                    'Transport' => ui_phrase('Transport'),
                                    'TransportUnit' => ui_phrase('Transport'),
                                    'TouristAttraction' => ui_phrase('Tourist Attraction'),
                                    'HotelRoom' => ui_phrase('Hotel'),
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
                                $dayText = $dayNumber > 0 ? (ui_phrase('Day') . ' ' . $dayNumber) : ui_phrase('Without Day');

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

                                if (in_array($serviceableType, ['Activity', 'FoodBeverage'], true)) {
                                    $descriptionLabel = $resolveValidationDescriptionLabel($item);
                                    $serviceableMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                    $mealLabels = $resolveMealLabelsFromMeta($serviceableMeta);
                                }
                            @endphp
                            @php
                                $contactData = $resolveValidationContactData($item);
                                $itemBrand = $resolveItemBrandForValidation($item);
                                $itemDayNumber = (int) ($item->day_number ?? 0);
                                if ($itemDayNumber <= 0) {
                                    $itemMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                    $itemDayNumber = (int) ($itemMeta['day_number'] ?? 0);
                                }
                                $itemServiceDateLabel = '-';
                                if ($quotationServiceDateBase) {
                                    $itemServiceDate = $quotationServiceDateBase->copy();
                                    if ($itemDayNumber > 1) {
                                        $itemServiceDate->addDays($itemDayNumber - 1);
                                    }
                                    $itemServiceDateLabel = $itemServiceDate->format('Y-m-d');
                                }
                            @endphp
                            <tr data-validation-item-row="{{ $item->id }}">
                                <td class="px-3 py-2 align-top">
                                    <div class="space-y-1">
                                        <input type="hidden" name="items[{{ $item->id }}][is_validated]" value="0">
                                        <input
                                            type="hidden"
                                            name="items[{{ $item->id }}][validation_notes]"
                                            value="{{ old('items.' . $item->id . '.validation_notes', (string) ($item->validation_notes ?? '')) }}"
                                            data-canonical-input="validation_notes-{{ $item->id }}"
                                        >
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                            <input type="checkbox" name="items[{{ $item->id }}][is_validated]" value="1" data-item-validated-checkbox="{{ $item->id }}" @checked((bool) ($item->is_validated ?? false)) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </label>
                                        <p data-item-feedback="{{ $item->id }}" class="hidden text-[11px]"></p>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <button type="button" data-open-validation-detail="{{ $item->id }}" class="text-left text-indigo-700 hover:underline dark:text-indigo-300">
                                        {{ $vendorProviderItemLabel }}
                                    </button>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <div class="text-gray-700 dark:text-gray-200">
                                        <div class="font-semibold">{{ $typeLabel }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $descriptionLabel }}</div>
                                    </div>
                                </td>
                                
                                <td class="px-3 py-2 align-top">
                                    <input type="hidden" name="items[{{ $item->id }}][qty]" value="{{ old('items.' . $item->id . '.qty', (int) ($item->qty ?? 1)) }}" data-canonical-input="qty-{{ $item->id }}">
                                    <input type="hidden" name="items[{{ $item->id }}][contract_rate]" value="{{ old('items.' . $item->id . '.contract_rate', (int) round((float) ($item->contract_rate ?? 0))) }}" data-canonical-input="contract_rate-{{ $item->id }}">
                                    <span class="text-xs text-gray-700 dark:text-gray-200" data-contract-rate-display="{{ $item->id }}">-</span>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="hidden" name="items[{{ $item->id }}][markup_type]" value="{{ old('items.' . $item->id . '.markup_type', $item->markup_type) }}" data-canonical-input="markup_type-{{ $item->id }}">
                                    <span class="text-xs text-gray-700 dark:text-gray-200" data-markup-type-display="{{ $item->id }}">{{ (old('items.' . $item->id . '.markup_type', $item->markup_type) === 'percent') ? ui_phrase('Percent') : ui_phrase('Fixed') }}</span> 
                                    <input type="hidden" name="items[{{ $item->id }}][markup]" value="{{ old('items.' . $item->id . '.markup', (int) round((float) ($item->markup ?? 0))) }}" data-canonical-input="markup-{{ $item->id }}">
                                    <span class="text-xs text-gray-700 dark:text-gray-200" data-markup-display="{{ $item->id }}">-</span>
                                </td>
                                <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-200" data-item-qty-display="{{ $item->id }}">{{ (int) ($item->qty ?? 0) }}</td>
                                <td class="px-3 py-2 align-top">
                                    <span class="text-xs text-gray-700 dark:text-gray-200" data-public-rate-display="{{ $item->id }}">-</span>
                                </td>
                                <td class="px-3 py-2 align-top text-xs text-gray-600 dark:text-gray-300" data-item-updated="{{ $item->id }}">
                                    {{ $item->validator?->name ?? '-' }}
                                    @if ($item->updated_at)
                                        <br><x-local-time :value="$item->updated_at" />
                                    @endif
                                    @if ((bool) ($item->is_validated ?? false))
                                        <x-ui.status-badge status="validated" :label="ui_phrase('Validated')" size="xs" />
                                    @else
                                        <x-ui.status-badge status="pending" :label="ui_phrase('Pending')" size="xs" />
                                    @endif
                                </td>
                                {{-- <td class="px-3 py-2 align-top" data-item-status="{{ $item->id }}">
                                </td> --}}
                                <td class="px-3 py-2 align-top">
                                    <button
                                        type="button"
                                        class="{{ (bool) ($item->is_validated ?? false) ? 'btn-outline-sm' : 'btn-primary-sm' }}"
                                        data-save-item="{{ $item->id }}"
                                        data-save-item-url="{{ route('quotations.validate.save-item', ['quotation' => $quotation, 'item' => $item]) }}"
                                        data-item-type="{{ $typeLabel }}"
                                        data-item-category="{{ $serviceableType }}"
                                        data-item-brand="{{ $itemBrand }}"
                                        data-item-name="{{ $descriptionLabel }}"
                                        data-vendor-name="{{ $contactData['vendor_name'] }}"
                                        data-contact-person="{{ $contactData['contact_name'] }}"
                                        data-contact-phone="{{ $contactData['contact_phone'] }}"
                                        data-contact-email="{{ $contactData['contact_email'] }}"
                                        data-contact-website="{{ $contactData['contact_website'] }}"
                                        data-item-qty="{{ (int) ($item->qty ?? 1) }}"
                                        data-item-pax="{{ $paxLabel }}"
                                        data-item-service-date="{{ $itemServiceDateLabel }}"
                                        data-last-validation-by="{{ (bool) ($item->is_validated ?? false) ? ($item->validator?->name ?? '') : '' }}"
                                        data-last-validation-at="{{ (bool) ($item->is_validated ?? false) ? optional($item->updated_at)->toIso8601String() : '' }}"
                                        data-last-validation-note="{{ (bool) ($item->is_validated ?? false) ? (string) ($item->validation_notes ?? '') : '' }}"
                                        data-action-label="{{ (bool) ($item->is_validated ?? false) ? ui_phrase('Revalidate') : ui_phrase('Validate') }}"
                                    >
                                        <span data-item-spinner="{{ $item->id }}" class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                                        <span data-item-save-label="{{ $item->id }}">{{ (bool) ($item->is_validated ?? false) ? ui_phrase('Revalidate') : ui_phrase('Validate') }}</span>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                        @else
                            <x-slot:emptyState>
                                <tr>
                                    <td colspan="11" class="px-3 py-4">
                                        <x-ui.empty-state :title="ui_phrase('No validation required items')" :description="ui_phrase('There are no items that require validation for this quotation.')" />
                                    </td>
                                </tr>
                            </x-slot:emptyState>
                        @endif
                    </tbody>
                </table>
                </div>
            </div>
            </div>

            <x-ui.action-panel :title="ui_phrase('Bulk Actions')" :description="ui_phrase('Save ongoing updates or finalize quotation validation when all required items are validated.')">
                <button type="submit" class="btn-secondary" data-save-progress-btn>
                    <span data-save-progress-spinner class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                    <span data-save-progress-label>{{ ui_phrase('Save Progress') }}</span>
                </button>
                <button
                    type="button"
                    class="btn-primary {{ (bool) ($progress['is_complete'] ?? false) ? '' : 'hidden' }}"
                    data-finalize-quotation-btn
                >
                    <span>{{ ui_phrase('Validate Quotation') }}</span>
                </button>
            </x-ui.action-panel>
        </form>
        <form id="quotation-finalize-form" method="POST" action="{{ route('quotations.validate.finalize', $quotation) }}" class="hidden">
            @csrf
        </form>
    </div>

    <x-modal name="validation-item-detail-modal" focusable maxWidth="2xl">
        <div
            id="validation-item-detail-modal-content"
            class="w-full rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900"
            data-detail-endpoint-template="{{ route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => '__ITEM__']) }}"
            data-contact-update-endpoint-template="{{ route('quotations.validate.update-item-contact', ['quotation' => $quotation, 'item' => '__ITEM__']) }}"
        >
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100" data-modal-title>{{ ui_phrase('item detail modal title') }}</h3>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-ghost px-2 py-1 text-xs" data-close-validation-modal>{{ ui_phrase('Close') }}</button>
                </div>
            </div>

            <div data-modal-loading class="mt-6 hidden flex justify-center">
                <span class="inline-block h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-indigo-500 dark:border-gray-600 dark:border-t-indigo-300"></span>
            </div>
            <div data-modal-error class="mt-4 hidden rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/30 dark:text-rose-300"></div>
            <div data-update-contact-feedback class="mt-4 hidden text-xs"></div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <div class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Vendor / Provider Information') }}</div>
                        <div class="mt-2">
                            <p class="font-semibold text-gray-900 dark:text-gray-100" data-contact-provider>-</p>
                            <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-300">
                                <span class="font-semibold">{{ ui_phrase('Address') }}:</span>
                                <span data-contact-address-display>-</span>
                            </p>
                        </div>
                    </div>
                    <div data-modal-current class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700"></div>
                    <div class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Communication Log') }}</div>
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('This section shows the rate currently used by this quotation item.') }}</p>
                        <div class="mt-2 space-y-2" data-validation-history>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('No communication history yet.') }}</p>
                        </div>
                    </div>
                </div>
                <div data-modal-contact class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Contact Form & Communication') }}</div>
                    <div class="mt-2 space-y-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Contact Person') }}</label>
                            <input type="text" class="app-input mt-1 text-xs" data-contact-name>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Phone') }}</label>
                            <input type="text" class="app-input mt-1 text-xs" data-contact-phone>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Email') }}</label>
                            <input type="email" class="app-input mt-1 text-xs" data-contact-email>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Website') }}</label>
                            <input type="text" class="app-input mt-1 text-xs" data-contact-website>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400">{{ ui_phrase('Communication Note') }}</label>
                            <textarea rows="4" class="app-input mt-1 text-xs" data-validation-note placeholder="{{ ui_phrase('Write communication summary for this validation item...') }}"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                <button type="button" class="btn-secondary-sm" data-close-validation-modal>{{ ui_phrase('Cancel') }}</button>
                <button type="button" class="btn-primary-sm" data-update-contact>
                    <span data-update-contact-spinner class="mr-1 hidden inline-block h-3 w-3 animate-spin rounded-full border border-current border-t-transparent align-[-1px]"></span>
                    <span data-update-contact-label>{{ ui_phrase('Update') }}</span>
                </button>
            </div>

        </div>
    </x-modal>

    <x-modal name="validate-item-confirm-modal" focusable maxWidth="4xl">
        <div id="validate-item-confirm-modal-content" class="w-full rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100" data-confirm-modal-title>{{ ui_phrase('Confirm Validation') }}</h3>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="space-y-4">
                    <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-800/70 dark:bg-amber-900/15">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-200">{{ ui_phrase('Validation Instructions') }}</h4>
                        <ol class="mt-2 list-decimal space-y-1 pl-4 text-xs text-amber-900 dark:text-amber-100">
                            <li>{{ ui_phrase('Confirm vendor/provider and item details before contacting.') }}</li>
                            <li>{{ ui_phrase('Update Contract Rate, Markup Type, and Markup based on latest vendor confirmation.') }}</li>
                            <li>{{ ui_phrase('Write communication Note to keep history clear and avoid duplicate follow-up.') }}</li>
                            <li>{{ ui_phrase('Check last validation info to decide whether re-contact is needed.') }}</li>
                            <li>{{ ui_phrase('Click Validate/Revalidate to save data and update validation status.') }}</li>
                        </ol>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Vendor / Provider') }}</h4>
                        <dl class="mt-2 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                            <div data-confirm-field="item-type">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Type') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-item-type></dd>
                            </div>
                            <div data-confirm-field="vendor-name">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Vendors/Providers Name') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-vendor-name></dd>
                            </div>
                            <div data-confirm-field="contact-person">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Contact Person') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-contact-person></dd>
                            </div>
                            <div data-confirm-field="contact-phone">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Phone') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-contact-phone></dd>
                            </div>
                            <div data-confirm-field="contact-email">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Email') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-contact-email></dd>
                            </div>
                            <div data-confirm-field="contact-website" class="sm:col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Website') }}</dt>
                                <dd class="mt-0.5 break-all font-semibold text-gray-800 dark:text-gray-100" data-confirm-contact-website></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Service Detail') }}</h4>
                        <dl class="mt-2 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                            <div data-confirm-field="item-name" class="sm:col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Name') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-item-name></dd>
                            </div>
                            <div data-confirm-field="item-pax">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Pax') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-item-pax></dd>
                            </div>
                            <div data-confirm-field="item-service-date">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Service Date') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-item-service-date></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900" data-confirm-latest-card>
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Latest Validation') }}</h4>
                        <dl class="mt-2 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                            <div data-confirm-field="last-validation-by">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Last Validation By') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-last-validation-by></dd>
                            </div>
                            <div data-confirm-field="last-validation-at">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Last Validation Date') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100" data-confirm-last-validation-at></dd>
                            </div>
                            <div data-confirm-field="last-validation-note" class="sm:col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Validation Note') }}</dt>
                                <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-100 whitespace-pre-line" data-confirm-last-validation-note></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ ui_phrase('Validation Form') }}</h4>
                        <dl class="mt-2 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Qty') }}</dt>
                        <input type="number" min="1" step="1" class="mt-1 app-input text-xs text-right" data-confirm-qty-input>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Contract Rate /pax') }}</dt>
                        <div class="mt-1 input-with-left-affix">
                            <input type="text" pattern="[0-9.]*" inputmode="numeric" class="app-input pl-14 text-right text-xs" data-confirm-contract-rate-input>
                            <span class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" data-confirm-contract-rate-badge>{{ \App\Support\Currency::meta(\App\Support\Currency::current())['symbol'] ?? (\App\Support\Currency::current() === 'USD' ? '$' : 'Rp') }}</span>
                        </div>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Markup Type') }}</dt>
                        <select class="mt-1 app-input text-xs" data-confirm-markup-type-input>
                            <option value="fixed">{{ ui_phrase('Fixed') }}</option>
                            <option value="percent">{{ ui_phrase('Percent') }}</option>
                        </select>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Markup /pax') }}</dt>
                        <div class="mt-1 input-with-left-affix">
                            <input type="text" pattern="[0-9.]*" inputmode="numeric" class="app-input pl-14 text-right text-xs" data-confirm-markup-input>
                            <span class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" data-confirm-markup-badge>{{ \App\Support\Currency::meta(\App\Support\Currency::current())['symbol'] ?? (\App\Support\Currency::current() === 'USD' ? '$' : 'Rp') }}</span>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Price') }}</dt>
                        <div class="mt-1 input-with-left-affix">
                            <input type="text" class="app-input pl-14 text-right text-xs bg-gray-50 dark:bg-gray-800" data-confirm-total-price-input readonly>
                            <span class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" data-confirm-total-price-badge>{{ \App\Support\Currency::meta(\App\Support\Currency::current())['symbol'] ?? (\App\Support\Currency::current() === 'USD' ? '$' : 'Rp') }}</span>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Validation Note') }}</dt>
                        <textarea rows="3" class="mt-1 app-input text-xs" data-confirm-validation-note placeholder="{{ ui_phrase('Write communication note for this validation...') }}"></textarea>
                    </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" class="btn-secondary-sm" x-data x-on:click.prevent="$dispatch('close-modal', 'validate-item-confirm-modal')">{{ ui_phrase('Cancel') }}</button>
                <button type="button" class="btn-primary-sm" data-confirm-validate-btn>{{ ui_phrase('Validate') }}</button>
            </div>
        </div>
    </x-modal>
@endsection
