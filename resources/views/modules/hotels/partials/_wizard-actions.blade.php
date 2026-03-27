<div class="mt-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        @if ($prevStep)
            <a href="{{ route('hotels.edit', [$hotel, 'step' => $prevStep]) }}" class="btn-ghost-sm" data-hotel-step-link data-hotel-step="{{ $prevStep }}">Previous</a>
        @endif
        @if ($nextStep)
            <a href="{{ route('hotels.edit', [$hotel, 'step' => $nextStep]) }}" class="btn-outline-sm" data-hotel-step-link data-hotel-step="{{ $nextStep }}">Next</a>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <button type="submit" class="btn-primary-sm">Save</button>
        <button type="submit" name="stay" value="1" class="btn-outline-sm">Save & Stay</button>
    </div>
</div>
