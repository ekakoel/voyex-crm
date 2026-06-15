<x-ui.table-action-dropdown :label="ui_phrase('Actions')">
    <a href="{{ $row['show_url'] }}"
        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
        <span>{{ ui_phrase('Detail') }}</span>
    </a>

    @if ($row['can_edit'])
        <a href="{{ $row['edit_url'] }}"
            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
            <span>{{ ui_phrase('Edit') }}</span>
        </a>
    @endif

    @if ($row['can_cancel'])
        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
        <x-ui.confirm-action :action="$row['cancel_url']" method="POST" :modal-name="$cancelModalName"
            :title="ui_phrase('Cancel Booking')" :message="ui_phrase('confirm cancel booking')" :impact-title="__('confirm.what_will_happen')" :impact-items="[
                ui_phrase('Booking status will change to cancelled.'),
                ui_phrase('Cancelled booking cannot continue to operation flow.'),
            ]"
            :notice-message="__('confirm.notification_after_action')" notice-tone="warning" :confirm-label="ui_phrase('Cancel Booking')" :trigger-label="ui_phrase('Cancel Booking')"
            trigger-icon="fa-solid fa-ban w-4"
            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20"
            confirm-class="btn-danger-sm" />
    @endif

    @if ($row['can_delete'])
        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
        <x-ui.confirm-action :action="$row['delete_url']" method="DELETE" :modal-name="$deleteModalName"
            :title="ui_phrase('Delete') . ' ' . ui_phrase('Booking')" :message="ui_phrase('confirm delete')" :impact-title="__('confirm.important_warning')" :impact-items="[
                __('confirm.delete_itinerary_info_1'),
                __('confirm.delete_itinerary_info_2'),
            ]"
            :notice-message="__('confirm.notification_after_action')" notice-tone="danger" :confirm-label="ui_phrase('Delete')" :trigger-label="ui_phrase('Delete')"
            trigger-icon="fa-solid fa-trash w-4"
            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
            confirm-class="btn-danger-sm" />
    @endif
</x-ui.table-action-dropdown>
