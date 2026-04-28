@extends('layouts.master')

@section('page_title', ui_phrase('modules_customers_import_page_title'))
@section('page_subtitle', ui_phrase('modules_customers_import_page_subtitle'))
@section('page_actions')
    <a href="{{ route('customers.index') }}"  class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--customers">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                @if (session('error'))
                    <div class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
                    <form method="POST" action="{{ route('customers.import.preview') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_customers_import_download_template_hint') }}</p>
                            <a href="{{ route('customers.import.template') }}"  class="btn-secondary">
                                {{ ui_phrase('modules_customers_import_download_template') }}
                            </a>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('modules_customers_import_csv_file') }}</label>
                            <input type="file" name="file" accept=".csv,text/csv" class="mt-1 w-full text-sm">
                            @error('file')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-lg mb-6 border border-dashed border-gray-300 p-4 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                            {{ ui_phrase('modules_customers_import_min_header') }}: <code>name</code><br>
                            {{ ui_phrase('modules_customers_import_optional_header') }}: <code>code, email, phone, address, country, customer_type, company_name</code><br>
                            {{ ui_phrase('modules_customers_import_customer_type_values') }}: <code>individual</code> / <code>company</code>
                        </div>

                        <div class="flex items-center gap-2">
                            <button  class="btn-primary">{{ ui_phrase('modules_customers_import_preview_button') }}</button>
                            <a href="{{ route('customers.index') }}"  class="btn-secondary">{{ ui_phrase('common_cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
            <aside  class="space-y-6 xl:col-span-4">
                <div class="rounded-xl border border-slate-200/80 bg-white p-5 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('modules_customers_import_tips_title') }}</p>
                    <p class="mt-2">{{ ui_phrase('modules_customers_import_tips_text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
