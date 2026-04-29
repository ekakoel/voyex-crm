@if($canUsers)
    <div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Recently Updated Users') }}</h3>
        <div class="mt-3 space-y-2">
            @forelse($recentUsers as $user)
                @php
                    $displayName = (string) $user->name;
                    $normalizedName = \App\Support\UiTranslator::normalizeToken($displayName);
                    $systemRoleTokens = ['reservation', 'administrator', 'manager', 'marketing', 'director', 'finance', 'editor', 'super_admin'];
                    if (in_array($normalizedName, $systemRoleTokens, true)) {
                        $displayName = ui_phrase($normalizedName);
                    }
                @endphp
                <a href="{{ route('users.edit', $user) }}" class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800" data-progressive-item>
                    <div class="flex items-center justify-between">
                        <p class="font-bold text-slate-700 dark:text-slate-200">{{ $displayName }}</p>
                        <p class="text-slate-500 dark:text-slate-400">{{ \App\Support\DateTimeDisplay::datetime($user->updated_at) }}</p>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </a>
            @empty
                <p class="text-xs text-slate-500 dark:text-slate-400" data-progressive-item>{{ ui_phrase('No users updated recently.') }}</p>
            @endforelse
        </div>
    </div>
@endif


