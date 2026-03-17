@php
    $user = auth()->user();
    $canComment = $user
        && $user->hasAnyRole(['Director', 'Manager', 'Marketing'])
        && (int) ($quotation->created_by ?? 0) !== (int) $user->id
        && ! $quotation->isFinal();
    $latestComment = $quotation->comments->first();
    $hasNewComment = $latestComment
        && $user
        && (int) ($latestComment->user_id ?? 0) !== (int) $user->id
        && optional($latestComment->created_at)->gt(now()->subDay());
    $editingId = (int) request('edit_comment_id', 0);
@endphp

<div class="module-card p-6 space-y-4">
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Comments</p>
        <span class="text-xs text-gray-400">{{ $quotation->comments->count() }} comment</span>
    </div>

    @if ($hasNewComment)
        <div class="rounded-lg mb-6 border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            Ada comment baru dari {{ $latestComment->user?->name ?? 'user' }}.
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($quotation->comments as $index => $comment)
            <div class="rounded-lg mb-6 border border-gray-200 p-3 text-xs text-gray-700 dark:border-gray-700 dark:text-gray-200 {{ $index === 0 ? 'bg-indigo-50/60 dark:bg-indigo-900/20' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="font-semibold text-gray-800 dark:text-gray-100">
                        {{ $comment->user?->name ?? 'Unknown' }}
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        @if ($index === 0)
                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:bg-indigo-800/60 dark:text-indigo-200">Newest</span>
                        @endif
                        <span>{{ $comment->created_at?->format('Y-m-d H:i') ?? '-' }}</span>
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
                            <button type="submit"  class="btn-primary-sm">Save</button>
                            <a href="{{ url()->current() }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
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
                        <a href="{{ url()->current() . '?edit_comment_id=' . $comment->id }}" class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Edit</a>
                        <form method="POST" action="{{ route('quotations.comments.destroy', [$quotation, $comment]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this comment?')"   class="btn-danger-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada comment.</p>
        @endforelse
    </div>

    @if ($canComment)
        <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-200">
                <i class="fa-solid fa-comment-dots mr-2"></i> Tambah Comment
            </summary>
            <form method="POST" action="{{ route('quotations.comments.store', $quotation) }}" class="mt-2 space-y-2">
                @csrf
                <textarea
                    name="comment_body"
                    rows="3"
                    class="w-full app-input"
                    placeholder="Tulis comment untuk quotation ini..."
                >{{ old('comment_body') }}</textarea>
                @error('comment_body')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <button type="submit"  class="btn-primary-sm">
                    Add Comment
                </button>
            </form>
        </details>
    @endif
</div>


