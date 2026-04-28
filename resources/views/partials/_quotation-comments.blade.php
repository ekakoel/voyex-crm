@php
    $user = auth()->user();
    $canComment = $user && ! $quotation->isFinal();
    $isCreator = $user && (int) ($quotation->created_by ?? 0) === (int) $user->id;
    $rootComments = $quotation->comments
        ->whereNull('parent_id')
        ->sortByDesc(fn ($comment) => optional($comment->created_at)->getTimestamp() ?? 0)
        ->values();
    $latestComment = $rootComments->first();
    $hasNewComment = $latestComment
        && $user
        && (int) ($latestComment->user_id ?? 0) !== (int) $user->id
        && optional($latestComment->created_at)->gt(now()->subDay());
    $editingId = (int) request('edit_comment_id', 0);
    $replyingToId = (int) request('reply_to_comment_id', 0);
@endphp

<div class="module-card p-6 space-y-4">
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('quotation_comments_title') }}</p>
        <span class="text-xs text-gray-400">{{ ui_choice('quotation_comments_count', (int) $quotation->comments->count(), ['count' => (int) $quotation->comments->count()]) }}</span>
    </div>

    @if ($hasNewComment)
        <div class="rounded-lg mb-6 border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            {{ ui_phrase('quotation_comments_new_comment_from', ['name' => $latestComment->user?->name ?? ui_phrase('common_unknown')]) }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($rootComments as $index => $comment)
            <div class="rounded-lg mb-6 border border-gray-200 p-3 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200 {{ $index === 0 ? 'bg-indigo-50/60 dark:bg-indigo-900/20' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="font-semibold text-gray-800 dark:text-gray-100">
                        {{ $comment->user?->name ?? ui_phrase('common_unknown') }}
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        @if ($index === 0)
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:bg-indigo-800/60 dark:text-indigo-200">{{ ui_phrase('common_newest') }}</span>
                        @endif
                        <span><x-local-time :value="$comment->created_at" /></span>
                    </div>
                </div>

                @if ($editingId === (int) $comment->id && $user && (int) ($comment->user_id ?? 0) === (int) $user->id)
                    <form method="POST" action="{{ route('quotations.comments.update', [$quotation, $comment]) }}" class="mt-2 space-y-2">
                        @csrf
                        @method('PUT')
                        <textarea name="comment_body" rows="3" class="w-full app-input">{{ old('comment_body', $comment->body) }}</textarea>
                        @error('comment_body')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <div class="flex items-center gap-2">
                            <button type="submit" class="btn-primary-sm">{{ ui_phrase('common_save') }}</button>
                            <a href="{{ url()->current() }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ ui_phrase('common_cancel') }}</a>
                        </div>
                    </form>
                @else
                    @php
                        $plainComment = trim(strip_tags((string) ($comment->body ?? '')));
                    @endphp
                    <div class="mt-1 text-gray-600 dark:text-gray-300">
                        {!! nl2br(e($plainComment)) !!}
                    </div>
                @endif

                @if ($user && (int) ($comment->user_id ?? 0) === (int) $user->id && ! $quotation->isFinal())
                    <div class="mt-2 flex items-center gap-2">
                        <a href="{{ url()->current() . '?edit_comment_id=' . $comment->id }}" class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">{{ ui_phrase('common_edit') }}</a>
                        <form method="POST" action="{{ route('quotations.comments.destroy', [$quotation, $comment]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('{{ ui_phrase('quotation_comments_delete_confirm') }}')" class="btn-danger-sm">
                                {{ ui_phrase('common_delete') }}
                            </button>
                        </form>
                    </div>
                @endif

                @if ($isCreator && ! $quotation->isFinal() && (int) ($comment->user_id ?? 0) !== (int) $user->id)
                    <div class="mt-2">
                        <a href="{{ url()->current() . '?reply_to_comment_id=' . $comment->id }}" class="text-[11px] font-medium text-emerald-700 hover:text-emerald-800 dark:text-emerald-300">
                            {{ ui_phrase('common_reply') }}
                        </a>
                    </div>
                @endif

                @php
                    $replies = $quotation->comments
                        ->where('parent_id', $comment->id)
                        ->sortBy(fn ($reply) => optional($reply->created_at)->getTimestamp() ?? 0)
                        ->values();
                @endphp
                @if ($replies->isNotEmpty())
                    <div class="mt-3 space-y-2 border-l-2 border-emerald-200 pl-3 dark:border-emerald-800">
                        @foreach ($replies as $reply)
                            <div class="rounded-lg border border-emerald-200/80 bg-emerald-50/50 p-2 text-xs dark:border-emerald-800 dark:bg-emerald-900/10">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $reply->user?->name ?? ui_phrase('common_unknown') }}</div>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400"><x-local-time :value="$reply->created_at" /></span>
                                </div>
                                <div class="mt-1 text-gray-600 dark:text-gray-300">{!! nl2br(e(trim(strip_tags((string) ($reply->body ?? ''))))) !!}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($isCreator && ! $quotation->isFinal() && $replyingToId === (int) $comment->id)
                    <form method="POST" action="{{ route('quotations.comments.store', $quotation) }}" class="mt-3 space-y-2 rounded-lg border border-emerald-200 bg-emerald-50/40 p-3 dark:border-emerald-800 dark:bg-emerald-900/10">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <textarea
                            name="comment_body"
                            rows="3"
                            class="w-full app-input"
                            placeholder="{{ ui_phrase('quotation_comments_reply_placeholder') }}"
                        >{{ old('comment_body') }}</textarea>
                        @error('comment_body')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('parent_id')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <div class="flex items-center gap-2">
                            <button type="submit" class="btn-primary-sm">{{ ui_phrase('quotation_comments_send_reply') }}</button>
                            <a href="{{ url()->current() }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">{{ ui_phrase('common_cancel') }}</a>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('quotation_comments_no_comments') }}</p>
        @endforelse
    </div>

    @if ($canComment)
        <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-200">
                <i class="fa-solid fa-comment-dots mr-2"></i> {{ ui_phrase('quotation_comments_add_comment') }}
            </summary>
            <form method="POST" action="{{ route('quotations.comments.store', $quotation) }}" class="mt-2 space-y-2">
                @csrf
                <textarea
                    name="comment_body"
                    rows="3"
                    class="w-full app-input"
                    placeholder="{{ ui_phrase('quotation_comments_comment_placeholder') }}"
                >{{ old('comment_body') }}</textarea>
                @error('comment_body')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn-primary-sm">
                    {{ ui_phrase('quotation_comments_add_comment') }}
                </button>
            </form>
        </details>
    @endif
</div>
