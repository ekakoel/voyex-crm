@php
    $actions = collect($availableActions ?? [])
        ->filter(fn ($action) => ($action['visible'] ?? true) === true)
        ->values();
    $primaryActions = $actions->where('priority', 'primary')->values();
    $secondaryActions = $actions->where('priority', 'secondary')->values();
    $moreActions = $actions->filter(fn ($action) => in_array($action['priority'] ?? '', ['danger', 'dropdown'], true))->values();
@endphp

@foreach ($primaryActions->merge($secondaryActions) as $action)
    @php
        $method = strtoupper((string) ($action['method'] ?? 'GET'));
        $icon = (string) ($action['icon'] ?? '');
        $confirm = (string) ($action['confirm_message'] ?? '');
        $openInNewTab = (string) ($action['key'] ?? '') === 'preview_download_pdf';
    @endphp
    @if ($method === 'MODAL')
        <x-quotation-action-button
            variant="{{ ($action['style'] ?? 'secondary') === 'primary' ? 'primary' : (($action['style'] ?? 'secondary') === 'danger' ? 'danger' : 'outline') }}"
            :icon="$icon"
            :label="$action['label'] ?? 'Action'"
            x-data
            x-on:click.prevent="$dispatch('open-modal', '{{ $action['modal'] ?? '' }}')"
        />
    @elseif ($method === 'GET')
        <x-quotation-action-button
            :href="$action['route']"
            variant="{{ ($action['style'] ?? 'secondary') === 'primary' ? 'primary' : (($action['style'] ?? 'secondary') === 'danger' ? 'danger' : 'outline') }}"
            :icon="$icon"
            :label="$action['label'] ?? 'Action'"
            :target="$openInNewTab ? '_blank' : null"
            :rel="$openInNewTab ? 'noopener' : null"
        />
    @else
        @if ($confirm !== '')
            <x-ui.confirm-action
                :action="$action['route']"
                :method="$method"
                :modal-name="'quotation-action-' . md5((string) $action['route'] . '|' . (string) ($action['label'] ?? ''))"
                :title="$action['label'] ?? 'Action'"
                :message="$confirm"
                :notice-message="__('confirm.notification_after_action')"
                :confirm-label="$action['label'] ?? ui_phrase('Confirm')"
                :trigger-label="$action['label'] ?? 'Action'"
                :trigger-icon="$icon !== '' ? ('fa-solid ' . $icon) : null"
                :trigger-class="(($action['style'] ?? 'secondary') === 'primary' ? 'btn-primary' : (($action['style'] ?? 'secondary') === 'danger' ? 'btn-danger-sm' : 'btn-outline'))"
                :confirm-class="(($action['style'] ?? 'secondary') === 'danger' ? 'btn-danger-sm' : 'btn-primary-sm')"
            />
        @else
            <form method="POST" action="{{ $action['route'] }}" class="inline-flex">
                @csrf
                @if (! in_array($method, ['POST'], true))
                    @method($method)
                @endif
                @foreach (($action['payload'] ?? []) as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <x-quotation-action-button
                    type="submit"
                    variant="{{ ($action['style'] ?? 'secondary') === 'primary' ? 'primary' : (($action['style'] ?? 'secondary') === 'danger' ? 'danger' : 'outline') }}"
                    :icon="$icon"
                    :label="$action['label'] ?? 'Action'"
                />
            </form>
        @endif
    @endif
@endforeach

@if ($moreActions->isNotEmpty())
    <div x-data="{ open: false }" class="relative inline-flex">
        <button type="button" class="btn-outline inline-flex items-center gap-2" x-on:click="open = ! open">
            <i class="fa-solid fa-ellipsis w-4 text-center"></i>
            <span>{{ ui_phrase('More Actions') }}</span>
        </button>
        <div
            x-show="open"
            x-cloak
            x-on:click.outside="open = false"
            class="absolute right-0 z-20 mt-10 w-56 rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-900"
        >
            @foreach ($moreActions as $action)
                @php
                    $method = strtoupper((string) ($action['method'] ?? 'GET'));
                    $label = ui_phrase((string) ($action['label'] ?? 'Action'));
                    $icon = (string) ($action['icon'] ?? '');
                    $confirm = (string) ($action['confirm_message'] ?? '');
                    $dropdownClass = 'flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800';
                    if (($action['style'] ?? '') === 'danger') {
                        $dropdownClass = 'flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-xs text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20';
                    }
                @endphp
                @if ($method === 'GET')
                    <a
                        href="{{ $action['route'] }}"
                        @if (($action['key'] ?? '') === 'preview_download_pdf')
                            target="_blank" rel="noopener"
                        @endif
                        class="{{ $dropdownClass }}"
                    >
                        @if ($icon !== '')<i class="fa-solid {{ $icon }} w-4 text-center"></i>@endif
                        <span>{{ $label }}</span>
                    </a>
                @else
                    @if ($confirm !== '')
                        <x-ui.confirm-action
                            :action="$action['route']"
                            :method="$method"
                            :modal-name="'quotation-action-dropdown-' . md5((string) $action['route'] . '|' . $label)"
                            :title="$label"
                            :message="$confirm"
                            :notice-message="__('confirm.notification_after_action')"
                            :confirm-label="$label"
                            :trigger-label="$label"
                            :trigger-icon="$icon !== '' ? ('fa-solid ' . $icon . ' w-4 text-center') : null"
                            :trigger-class="$dropdownClass"
                            :confirm-class="(($action['style'] ?? '') === 'danger' ? 'btn-danger-sm' : 'btn-primary-sm')"
                        />
                    @else
                        <form method="POST" action="{{ $action['route'] }}">
                            @csrf
                            @if (! in_array($method, ['POST'], true))
                                @method($method)
                            @endif
                            @foreach (($action['payload'] ?? []) as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            <button type="submit" class="{{ $dropdownClass }}">
                                @if ($icon !== '')<i class="fa-solid {{ $icon }} w-4 text-center"></i>@endif
                                <span>{{ $label }}</span>
                            </button>
                        </form>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
@endif
