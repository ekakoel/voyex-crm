@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--transports">
        <div class="module-grid-9-3">
            <div class="module-grid-main">
                <div class="module-form-wrap">
                    <form method="POST" action="{{ route('transports.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('modules.transports._form', ['buttonLabel' => 'Save Transport'])
                    </form>
                </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Info</p>
                    <p class="mt-2">Lengkapi provider, unit transport, dan rate agar layanan siap dijual.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection


