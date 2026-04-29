@php($vendor = $vendor ?? null)

<div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ $title ?? 'Vendor Information' }}
    </p>
    <dl class="mt-3 space-y-2">
        @if (filled($vendor?->name))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Vendor Name') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->name }}</dd>
            </div>
        @endif
        @if (filled($vendor?->contact_name))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Contact Name') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->contact_name }}</dd>
            </div>
        @endif
        @if (filled($vendor?->contact_phone))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Phone') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->contact_phone }}</dd>
            </div>
        @endif
        @if (filled($vendor?->contact_email))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Email') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->contact_email }}</dd>
            </div>
        @endif
        @if (filled($vendor?->website))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Website') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->website }}</dd>
            </div>
        @endif
        @if (filled($vendor?->location))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Location') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->location }}</dd>
            </div>
        @endif
        @if (filled($vendor?->address))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Address') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->address }}</dd>
            </div>
        @endif
        @if (filled($vendor?->city))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('City') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->city }}</dd>
            </div>
        @endif
        @if (filled($vendor?->province))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Province') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->province }}</dd>
            </div>
        @endif
        @if (filled($vendor?->country))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Country') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->country }}</dd>
            </div>
        @endif
        @if (filled($vendor?->timezone))
            <div class="grid grid-cols-[110px_1fr] gap-2">
                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Timezone') }}</dt>
                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $vendor->timezone }}</dd>
            </div>
        @endif
    </dl>
</div>
