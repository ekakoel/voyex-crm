@php($contextSuffix = ($context ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop')

<x-ui.table-action-dropdown :label="ui_phrase('Actions')">
    <x-slot:trigger>
        <i class="fa-solid fa-ellipsis"></i>
    </x-slot:trigger>

    <a href="{{ $row['show_url'] }}"
        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
        <span>{{ ui_phrase('View') }}</span>
    </a>

    @if ($row['can_duplicate'])
        <x-ui.confirm-action :action="$row['duplicate_url']" method="POST" :modal-name="'itinerary-index-duplicate-' . $contextSuffix . '-' . $itinerary->id"
            :title="ui_phrase('Duplicate Itinerary')" :message="ui_phrase('confirm duplicate')" :impact-title="__('confirm.what_will_happen')" :impact-items="[
                __('confirm.duplicate_itinerary_info_1'),
                __('confirm.duplicate_itinerary_info_2'),
                __('confirm.duplicate_itinerary_info_3'),
            ]"
            :notice-message="__('confirm.notification_after_action')" notice-tone="info" :confirm-label="ui_phrase('Duplicate')" :trigger-label="ui_phrase('Duplicate')"
            trigger-icon="fa-solid fa-copy w-4 text-gray-500 dark:text-gray-400"
            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
            confirm-class="btn-primary-sm" />
    @endif

    @if ($row['can_edit'])
        <a href="{{ $row['edit_url'] }}"
            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
            <span>{{ ui_phrase('Edit') }}</span>
        </a>
    @endif

    @if ($row['can_delete'])
        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
        <x-ui.confirm-action :action="$row['destroy_url']" method="DELETE" :modal-name="'itinerary-index-delete-' . $contextSuffix . '-' . $itinerary->id"
            :title="ui_phrase('Delete Itinerary')" :message="ui_phrase('Are you sure you want to delete this itinerary?')" :impact-title="__('confirm.important_warning')" :impact-items="[
                __('confirm.delete_itinerary_info_1'),
                __('confirm.delete_itinerary_info_2'),
                __('confirm.delete_itinerary_info_3'),
            ]"
            :notice-message="__('confirm.notification_after_action')" notice-tone="danger" :confirm-label="ui_phrase('Delete')" :trigger-label="ui_phrase('Delete')"
            trigger-icon="fa-solid fa-trash w-4"
            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
            confirm-class="btn-danger-sm" />
    @endif
</x-ui.table-action-dropdown>
