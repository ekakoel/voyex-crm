<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HandlesActivityTimelineAjax
{
    protected function wantsActivityTimelineFragment(Request $request): bool
    {
        return (string) $request->header('X-Activity-Timeline-Ajax', '') === '1';
    }

    protected function activityTimelineFragmentResponse($activities)
    {
        return response()
            ->view('components.activity-timeline', compact('activities'))
            ->header('X-Activity-Timeline-Fragment', '1');
    }
}
