@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--activities">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('activities.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.activities._form', ['buttonLabel' => 'Save Activity'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Lengkapi vendor, tipe activity, dan pricing agar activity siap dipakai di itinerary.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection




