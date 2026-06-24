@extends('layouts.master')
@section('page_title', ui_phrase('Itineraries'))
@section('page_subtitle', ui_phrase('Manage itinerary records.'))
@section('page_actions')
    <a href="{{ route('itineraries.create') }}" class="btn-primary">
        {{ ui_phrase('Create Itinerary') }}
    </a>
@endsection
@section('content')
    <div class="space-y-5 module-page module-page--itineraries" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main min-w-0">
            <div data-service-filter-results>
                <div class="app-card p-4 mb-3">
                    <form method="GET" action="{{ route('itineraries.index') }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" data-service-filter-form
                        data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input type="text" value="{{ request('title') }}"
                            placeholder="{{ ui_phrase('Search') }}"
                            class="app-input sm:col-span-2 lg:col-span-2" data-filter-title-visible
                            data-filter-min-text="3">
                        <input type="hidden" name="title" value="{{ request('title') }}"
                            data-service-filter-input data-filter-title-hidden>
                        <select name="destination_id" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('All destinations') }}</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>
                                    {{ $destination->name }}</option>
                            @endforeach
                        </select>
                        <input name="duration" type="number" min="1" value="{{ request('duration') }}"
                            placeholder="{{ ui_phrase('Duration (days)') }}" class="app-input"
                            data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                    {{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div
                            class="flex items-center gap-2 sm:col-span-2 lg:col-span-4 filter-actions h-[42px]">
                            <a href="{{ route('itineraries.index', ['reset' => 1]) }}"
                                class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                                data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="table-header">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="w-1/2 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Title') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Duration') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Quotation') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Capacity') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Item List') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($itineraryRows as $row)
                                    @php($itinerary = $row['itinerary'])
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                            {{ $row['row_number'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="font-medium">{{ $row['title_with_highlight'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ ui_phrase('by :name', ['name' => $row['creator_display_name']]) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>{{ $row['duration_label'] }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $row['destination_label'] }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @if ($row['quotation_count'] > 0)
                                                <div class="relative inline-block text-left itinerary-items-popover"
                                                    data-popover-root>
                                                    <button type="button"
                                                        class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200"
                                                        data-popover-trigger aria-expanded="false" aria-haspopup="true">
                                                        {{ $row['quotation_count'] }}
                                                    </button>
                                                    <div class="hidden w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                                        data-popover-panel role="dialog"
                                                        aria-label="{{ ui_phrase('Quotation list') }}"
                                                        style="position: fixed; z-index: 9999;">
                                                        <span
                                                            class="pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700"
                                                            data-popover-arrow aria-hidden="true"></span>
                                                        <p
                                                            class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                            {{ ui_phrase('Quotation') }}</p>
                                                        <ul class="space-y-1 text-xs text-gray-700 dark:text-gray-200">
                                                            @foreach ($row['quotation_links'] as $quotationLink)
                                                                <li>
                                                                    <a href="{{ $quotationLink['show_url'] }}"
                                                                        class="hover:underline">
                                                                        {{ $quotationLink['display_number'] }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                                                    0
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <span
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
                                                {{ $row['total_capacity'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @include('modules.itineraries.partials._index-item-popover', ['row' => $row])
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                @include('modules.itineraries.partials._index-action-dropdown', [
                                                    'row' => $row,
                                                    'itinerary' => $itinerary,
                                                    'context' => 'desktop',
                                                ])
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Itineraries')]) }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($itineraryRows as $row)
                        @php($itinerary = $row['itinerary'])
                        <div class="app-card p-4">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $row['title_with_highlight'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('by :name', ['name' => $row['creator_display_name']]) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $row['duration_label'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Quotation') }}:
                                {{ $row['quotation_count'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Capacity') }}:
                                {{ $row['total_capacity'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $row['destination_label'] }}</p>
                            <div class="mt-3">
                                @include('modules.itineraries.partials._index-item-popover', ['row' => $row])
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                @include('modules.itineraries.partials._index-action-dropdown', [
                                    'row' => $row,
                                    'itinerary' => $itinerary,
                                    'context' => 'mobile',
                                ])
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Itineraries')]) }}</div>
                    @endforelse
                </div>
                <div class="mt-3">{{ $itineraries->links() }}</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const state = window.__itineraryPopoverState || {
                popovers: [],
                observer: null,
                boundGlobals: false,
            };
            window.__itineraryPopoverState = state;

            const positionPanel = function(trigger, panel) {
                const rect = trigger.getBoundingClientRect();
                const arrow = panel.querySelector('[data-popover-arrow]');
                const gap = 8;
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const preferredWidth = viewportWidth >= 768 ? 288 : Math.min(288, viewportWidth - (gap * 2));
                panel.style.width = preferredWidth + 'px';
                const panelWidth = panel.offsetWidth || preferredWidth;
                const panelHeight = panel.offsetHeight || 260;

                // Default placement: on the right side of the trigger button.
                let placement = 'right';
                let left = rect.right + gap;
                let top = rect.top + (rect.height / 2);

                // If right side overflows, fallback:
                // - desktop/tablet => left side of trigger
                // - mobile => below trigger
                if (left + panelWidth > viewportWidth - gap) {
                    if (viewportWidth >= 768) {
                        placement = 'left';
                        left = rect.left - panelWidth - gap;
                    } else {
                        placement = 'bottom';
                        left = rect.right - panelWidth;
                        top = rect.bottom + gap;
                    }
                }

                if (placement === 'right' || placement === 'left') {
                    const minTopCenter = gap + (panelHeight / 2);
                    const maxTopCenter = viewportHeight - gap - (panelHeight / 2);
                    top = Math.min(Math.max(top, minTopCenter), maxTopCenter);
                    panel.style.transform = 'translateY(-50%)';
                } else {
                    if (top + panelHeight > viewportHeight - gap) {
                        top = rect.top - panelHeight - gap;
                    }
                    panel.style.transform = 'none';
                }

                left = Math.max(gap, Math.min(left, viewportWidth - panelWidth - gap));
                top = Math.max(gap, Math.min(top, viewportHeight - gap));

                panel.style.left = left + 'px';
                panel.style.top = top + 'px';

                if (arrow) {
                    const triggerCenterY = rect.top + (rect.height / 2);
                    const panelTop = placement === 'right' || placement === 'left' ?
                        (top - (panelHeight / 2)) :
                        top;
                    const minArrowTop = 14;
                    const maxArrowTop = Math.max(minArrowTop, panelHeight - 14);
                    const alignedArrowTop = Math.min(
                        maxArrowTop,
                        Math.max(minArrowTop, (triggerCenterY - panelTop))
                    );

                    if (placement === 'right') {
                        arrow.className =
                            'pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-r-[10px] border-r-gray-700 dark:border-r-gray-700';
                        arrow.style.left = '-10px';
                        arrow.style.right = 'auto';
                        arrow.style.top = alignedArrowTop + 'px';
                        arrow.style.transform = 'translateY(-50%)';
                    } else if (placement === 'left') {
                        arrow.className =
                            'pointer-events-none absolute h-0 w-0 border-y-[8px] border-y-transparent border-l-[10px] border-l-gray-700 dark:border-l-gray-700';
                        arrow.style.left = 'auto';
                        arrow.style.right = '-10px';
                        arrow.style.top = alignedArrowTop + 'px';
                        arrow.style.transform = 'translateY(-50%)';
                    } else {
                        arrow.className =
                            'pointer-events-none absolute h-0 w-0 border-x-[8px] border-x-transparent border-b-[10px] border-b-gray-700 dark:border-b-gray-700';
                        const triggerCenterX = rect.left + (rect.width / 2);
                        const alignedArrowLeft = Math.min(
                            panelWidth - 14,
                            Math.max(14, triggerCenterX - left)
                        );
                        arrow.style.left = alignedArrowLeft + 'px';
                        arrow.style.right = 'auto';
                        arrow.style.top = '-12px';
                        arrow.style.transform = 'translateX(-50%)';
                    }
                }
            };

            const closeAll = function() {
                state.popovers.forEach(function(entry) {
                    const panel = entry.panel;
                    const trigger = entry.trigger;
                    if (panel) {
                        panel.classList.add('hidden');
                    }
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            const openPanel = function(trigger, panel) {
                panel.style.visibility = 'hidden';
                panel.classList.remove('hidden');
                positionPanel(trigger, panel);
                panel.style.visibility = '';
            };

            const cleanupDetachedPopovers = function() {
                state.popovers = state.popovers.filter(function(entry) {
                    const isTriggerAlive = document.body.contains(entry.trigger);
                    const isRootAlive = document.body.contains(entry.root);
                    if (isTriggerAlive && isRootAlive) {
                        return true;
                    }
                    if (entry.panel && entry.panel.parentNode === document.body) {
                        entry.panel.remove();
                    }
                    return false;
                });
            };

            const bindRoots = function(scope) {
                const searchRoot = scope instanceof Element || scope instanceof Document ? scope : document;
                const roots = Array.from(searchRoot.querySelectorAll('[data-popover-root]'));
                roots.forEach(function(root) {
                    if (root.dataset.popoverBound === '1') {
                        return;
                    }
                    root.dataset.popoverBound = '1';

                    const trigger = root.querySelector('[data-popover-trigger]');
                    const panel = root.querySelector('[data-popover-panel]');
                    if (!trigger || !panel) {
                        root.dataset.popoverBound = '0';
                        return;
                    }
                    // Move panel to body to avoid fixed-position offset caused by transformed ancestors.
                    document.body.appendChild(panel);
                    state.popovers.push({
                        root: root,
                        trigger: trigger,
                        panel: panel
                    });

                    trigger.addEventListener('click', function(event) {
                        event.stopPropagation();
                        const isHidden = panel.classList.contains('hidden');
                        closeAll();
                        if (isHidden) {
                            openPanel(trigger, panel);
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                    });
                });
            };

            if (!state.boundGlobals) {
                state.boundGlobals = true;

                document.addEventListener('click', function(event) {
                    cleanupDetachedPopovers();
                    const clickedInsidePopover = state.popovers.some(function(entry) {
                        return entry.root.contains(event.target) || entry.panel.contains(event
                            .target);
                    });
                    if (!clickedInsidePopover) {
                        closeAll();
                    }
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        closeAll();
                    }
                });

                window.addEventListener('resize', closeAll);
                window.addEventListener('scroll', function(event) {
                    const scrollTarget = event.target;
                    const isScrollingInsidePopover = scrollTarget instanceof Element && state.popovers.some(
                        function(entry) {
                            return entry.panel && entry.panel.contains(scrollTarget);
                        });
                    if (isScrollingInsidePopover) {
                        return;
                    }
                    closeAll();
                }, true);
            }

            bindRoots(document);
            cleanupDetachedPopovers();

            if (!state.observer) {
                state.observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (!(node instanceof Element)) {
                                return;
                            }
                            if (node.matches('[data-popover-root]')) {
                                bindRoots(node.parentElement || document);
                            } else if (node.querySelector('[data-popover-root]')) {
                                bindRoots(node);
                            }
                        });
                    });
                    cleanupDetachedPopovers();
                });
                state.observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            const filterForm = document.querySelector('[data-service-filter-form]');
            if (filterForm) {
                const minFilterLength = 3;
                const textFilterInputs = Array.from(filterForm.querySelectorAll(
                    'input[type="text"], input[type="search"]'));
                const titleInputVisible = filterForm.querySelector('[data-filter-title-visible]');
                const titleInputHidden = filterForm.querySelector('[data-filter-title-hidden]');
                let lastSubmittedTitleValue = String(titleInputHidden?.value || '').trim();

                const isTextFilterValueValid = function(value) {
                    const normalized = String(value || '').trim();
                    return normalized === '' || normalized.length >= minFilterLength;
                };

                const isAllTextFiltersValid = function() {
                    return textFilterInputs.every(function(input) {
                        return isTextFilterValueValid(input.value);
                    });
                };

                const syncInputValidityMessage = function(input) {
                    if (!input) return;
                    if (isTextFilterValueValid(input.value)) {
                        input.setCustomValidity('');
                        return;
                    }
                    input.setCustomValidity(
                        '{{ ui_phrase('Please enter at least :count characters before filtering.', ['count' => 3]) }}'
                        );
                };

                textFilterInputs.forEach(function(input) {
                    input.addEventListener('input', function() {
                        syncInputValidityMessage(input);
                        if (input !== titleInputVisible) {
                            return;
                        }
                        if (!titleInputHidden) {
                            return;
                        }
                        const currentValue = String(titleInputVisible.value || '').trim();
                        if (currentValue !== '' && currentValue.length < minFilterLength) {
                            return;
                        }
                        titleInputHidden.value = currentValue;
                        if (currentValue === lastSubmittedTitleValue) {
                            return;
                        }
                        lastSubmittedTitleValue = currentValue;
                        filterForm.requestSubmit();
                    });

                    input.addEventListener('blur', function() {
                        syncInputValidityMessage(input);
                        if (!isAllTextFiltersValid()) {
                            return;
                        }
                        if (input === titleInputVisible) {
                            const currentValue = String(titleInputVisible?.value || '').trim();
                            if (currentValue !== '' && currentValue.length < minFilterLength) {
                                return;
                            }
                            if (titleInputHidden) {
                                titleInputHidden.value = currentValue;
                            }
                            if (currentValue === lastSubmittedTitleValue) {
                                return;
                            }
                            lastSubmittedTitleValue = currentValue;
                        }
                        filterForm.requestSubmit();
                    });

                    input.addEventListener('keydown', function(event) {
                        if (event.key !== 'Enter' && event.key !== 'Tab') {
                            return;
                        }
                        syncInputValidityMessage(input);
                        if (!isAllTextFiltersValid()) {
                            event.preventDefault();
                            filterForm.reportValidity();
                            return;
                        }
                        if (input === titleInputVisible) {
                            const currentValue = String(titleInputVisible?.value || '').trim();
                            if (currentValue !== '' && currentValue.length < minFilterLength) {
                                event.preventDefault();
                                filterForm.reportValidity();
                                return;
                            }
                            if (titleInputHidden) {
                                titleInputHidden.value = currentValue;
                            }
                            if (currentValue === lastSubmittedTitleValue) {
                                return;
                            }
                            lastSubmittedTitleValue = currentValue;
                        }
                        filterForm.requestSubmit();
                    });
                });

                filterForm.addEventListener('submit', function(event) {
                    // The interactive event listeners (input, blur, change) are responsible
                    // for providing validation feedback. This handler's only job is to
                    // ensure the hidden title field is populated correctly before submission.
                    if (titleInputVisible && titleInputHidden) {
                        const normalizedTitle = String(titleInputVisible.value || '').trim();
                        titleInputHidden.value = normalizedTitle;
                        lastSubmittedTitleValue = normalizedTitle;
                    }
                });

                // Block global service-filter auto-trigger on select/number change when title is non-empty but < min length.
                filterForm.addEventListener('change', function(event) {
                    if (!titleInputVisible || !titleInputHidden) {
                        return;
                    }
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement ||
                            target instanceof HTMLTextAreaElement)) {
                        return;
                    }
                    if (!target.matches('[data-service-filter-input]')) {
                        return;
                    }

                    // Special handling for duration filter: always allow submission.
                    if (target.name === 'duration') {
                        // No further checks, let the form submit normally.
                        // The backend will handle the numerical duration validation.
                        return;
                    }

                    const titleValue = String(titleInputVisible.value || '').trim();
                    if (titleValue !== '' && titleValue.length < minFilterLength) {
                        syncInputValidityMessage(titleInputVisible);
                        event.stopImmediatePropagation();
                        event.preventDefault();
                        filterForm.reportValidity();
                    }
                }, true);
            }
        });
    </script>
@endpush
