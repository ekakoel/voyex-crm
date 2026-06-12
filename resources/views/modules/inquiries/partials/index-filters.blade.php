<form method="GET" action="{{ route('inquiries.index') }}"
    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" data-service-filter-form data-filter-min-text="3"
    data-disable-submit-lock="1" data-page-spinner="off">
    <input type="hidden" name="tab" value="{{ $selectedTab }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}"
        class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input data-filter-min-text="3">
    <select name="priority" class="app-input" data-service-filter-input>
        <option value="">{{ ui_phrase('Priority') }}</option>
        @foreach (['low', 'normal', 'high'] as $priority)
            <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ui_phrase($priority) }}
            </option>
        @endforeach
    </select>
    <select name="per_page" class="app-input" data-service-filter-input>
        @foreach ([10, 25, 50, 100] as $size)
            <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                {{ ui_phrase(':size/page', ['size' => $size]) }}
            </option>
        @endforeach
    </select>
    <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3 filter-actions h-[42px]">
        <a href="{{ route('inquiries.index', ['tab' => $selectedTab]) }}"
            class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
            data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
    </div>
</form>
