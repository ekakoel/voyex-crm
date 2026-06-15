<x-ui.table-action-dropdown :label="ui_phrase('Actions')">
    <a href="{{ $row['edit_url'] }}"
        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
        <span>{{ ui_phrase('Edit') }}</span>
    </a>

    @if ($row['can_delete'])
        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
        <x-ui.confirm-action
            :action="$row['delete_url']"
            method="DELETE"
            :modal-name="$modalName"
            :title="ui_phrase('Delete') . ' ' . ui_phrase('Currency')"
            :message="ui_phrase('confirm delete')"
            :impact-title="__('confirm.important_warning')"
            :impact-items="[
                __('confirm.delete_itinerary_info_1'),
                __('confirm.delete_itinerary_info_2'),
            ]"
            :notice-message="__('confirm.notification_after_action')"
            notice-tone="danger"
            :confirm-label="ui_phrase('Delete')"
            :trigger-label="ui_phrase('Delete')"
            trigger-icon="fa-solid fa-trash w-4"
            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
            confirm-class="btn-danger-sm"
        />
    @endif
</x-ui.table-action-dropdown>
