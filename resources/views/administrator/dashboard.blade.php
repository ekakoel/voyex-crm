@extends('layouts.master')

@section('page_title', __('ui.administrator_dashboard.page_title'))
@section('page_subtitle', __('ui.administrator_dashboard.page_subtitle'))
@section('page_actions')
    <div class="flex items-center gap-2">
        @if($canUsers)
            <a href="{{ route('users.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus-circle mr-2"></i>{{ __('ui.administrator_dashboard.actions.new_user') }}
            </a>
        @endif
    </div>
@endsection

@section('content')
<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60" data-page-spinner="off" data-background-load-page="1">
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        <section class="xl:col-span-9 space-y-3">
            <section class="sa-card p-5 dashboard-widget" data-dashboard-widget="system-management" data-endpoint="{{ $widgetEndpoints['system-management'] ?? '' }}">
                <div class="dashboard-widget-body">
                    @include('administrator.dashboard.partials._skeleton', ['title' => __('ui.administrator_dashboard.sections.system_management')])
                </div>
            </section>

            <section class="sa-card p-5 dashboard-widget" data-dashboard-widget="operational-overview" data-endpoint="{{ $widgetEndpoints['operational-overview'] ?? '' }}">
                <div class="dashboard-widget-body">
                    @include('administrator.dashboard.partials._skeleton', ['title' => __('ui.administrator_dashboard.sections.operational_overview')])
                </div>
            </section>

            <section class="sa-card p-5 dashboard-widget" data-dashboard-widget="master-data-catalog" data-endpoint="{{ $widgetEndpoints['master-data-catalog'] ?? '' }}">
                <div class="dashboard-widget-body">
                    @include('administrator.dashboard.partials._skeleton', ['title' => __('ui.administrator_dashboard.sections.master_data_catalog')])
                </div>
            </section>
        </section>

        <aside class="xl:col-span-3 space-y-3">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.administrator_dashboard.sections.quick_actions') }}</h3>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @if($canUsers)
                    <a href="{{ route('users.index') }}" class="btn-secondary-sm text-center"><i class="fa-solid fa-user-gear mr-2"></i>{{ ui_term('users') }}</a>
                    @endif
                    @if($canRoles)
                    <a href="{{ route('roles.index') }}" class="btn-secondary-sm text-center"><i class="fa-solid fa-user-shield mr-2"></i>{{ ui_term('roles') }}</a>
                    @endif
                    @if($canServices)
                    <a href="{{ route('services.index') }}" class="btn-secondary-sm text-center"><i class="fa-solid fa-cubes mr-2"></i>{{ ui_term('modules') }}</a>
                    @endif
                    @if($canVendors)
                    <a href="{{ route('vendors.index') }}" class="btn-secondary-sm text-center"><i class="fa-solid fa-handshake mr-2"></i>{{ ui_term('vendors') }}</a>
                    @endif
                </div>
            </div>

            <section class="sa-card p-4 dashboard-widget" data-dashboard-widget="pending-quotations" data-endpoint="{{ $widgetEndpoints['pending-quotations'] ?? '' }}">
                <div class="dashboard-widget-body">
                    @include('administrator.dashboard.partials._skeleton', ['title' => __('ui.administrator_dashboard.sections.pending_quotations'), 'compact' => true])
                </div>
            </section>

            <section class="sa-card p-4 dashboard-widget" data-dashboard-widget="recent-users" data-endpoint="{{ $widgetEndpoints['recent-users'] ?? '' }}">
                <div class="dashboard-widget-body">
                    @include('administrator.dashboard.partials._skeleton', ['title' => __('ui.administrator_dashboard.sections.recently_updated_users'), 'compact' => true])
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const i18n = {
            retry: @json(__('ui.administrator_dashboard.js.retry')),
            failed_to_load: @json(__('ui.administrator_dashboard.js.failed_to_load')),
        };
        const widgets = document.querySelectorAll('[data-dashboard-widget]');
        if (!widgets.length) return;
        const WIDGET_BATCH_DELAY = 180;
        const ITEM_REVEAL_STEP_MS = 85;

        const renderError = (container, message, endpoint) => {
            container.innerHTML = `
                <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-3 text-xs text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-200">
                    <p>${message}</p>
                    <button type="button" class="btn-secondary-sm mt-2" data-dashboard-widget-retry="${endpoint}">
                        ${i18n.retry}
                    </button>
                </div>
            `;
        };

        const stageWidgetItems = (body) => {
            const items = Array.from(body.querySelectorAll('[data-progressive-item]'));
            if (!items.length) return;

            items.forEach((item) => item.classList.add('dashboard-item-pending'));
            items.forEach((item, index) => {
                window.setTimeout(() => {
                    item.classList.remove('dashboard-item-pending');
                    item.classList.add('dashboard-item-ready');
                }, ITEM_REVEAL_STEP_MS * index);
            });
        };

        const loadWidget = async (widgetNode) => {
            const endpoint = String(widgetNode.getAttribute('data-endpoint') || '').trim();
            const body = widgetNode.querySelector('.dashboard-widget-body');
            if (!endpoint || !body) return;

            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                if (!payload || payload.ok !== true || !payload.html) {
                    throw new Error('Invalid payload');
                }

                body.innerHTML = payload.html;
                body.classList.add('is-loaded');
                stageWidgetItems(body);
            } catch (_) {
                renderError(body, i18n.failed_to_load, endpoint);
            }
        };

        const queueLoadWidgets = async () => {
            for (let index = 0; index < widgets.length; index += 1) {
                await loadWidget(widgets[index]);
                if (index < widgets.length - 1) {
                    await new Promise((resolve) => window.setTimeout(resolve, WIDGET_BATCH_DELAY));
                }
            }
        };

        queueLoadWidgets();

        document.addEventListener('click', (event) => {
            const retryBtn = event.target.closest('[data-dashboard-widget-retry]');
            if (!retryBtn) return;

            const endpoint = String(retryBtn.getAttribute('data-dashboard-widget-retry') || '').trim();
            if (!endpoint) return;
            const widgetNode = retryBtn.closest('[data-dashboard-widget]');
            if (!widgetNode) return;

            const body = widgetNode.querySelector('.dashboard-widget-body');
            if (body) {
                body.innerHTML = '<div class="dashboard-skeleton-line w-1/2"></div><div class="dashboard-skeleton-line w-full mt-2"></div>';
            }
            loadWidget(widgetNode);
        });
    })();
</script>
@endpush
