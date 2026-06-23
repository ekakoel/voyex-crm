<x-ui.table-action-dropdown :label="ui_phrase('Actions')">
    <a href="{{ $row['show_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
        <span>{{ ui_phrase('View') }}</span>
    </a>
    <a href="{{ $row['edit_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
        <span>{{ ui_phrase('Edit') }}</span>
    </a>
    @if ($row['can_manage_activation'])
        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
        <x-ui.confirm-action
            :action="$row['toggle_url']"
            method="PATCH"
            :modal-name="$modalName"
            :title="$row['toggle_title']"
            :message="$row['toggle_message']"
            :notice-message="__('confirm.notification_after_action')"
            :confirm-label="$row['toggle_confirm_label']"
            :trigger-label="$row['toggle_trigger_label']"
            :trigger-icon="$row['toggle_trigger_icon']"
            :trigger-class="$row['toggle_trigger_class']"
            confirm-class="btn-primary-sm"
        />
    @endif
</x-ui.table-action-dropdown>
