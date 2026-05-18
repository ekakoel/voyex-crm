@php
    $rules = old('cancellation_rules', $cancellationPolicyRules ?? []);
    if (! is_array($rules)) {
        $rules = [];
    }
@endphp

<div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700" id="cancellation-policy-editor">
    <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Cancellation Policy') }}</p>
        <button type="button" class="btn-outline-sm" id="add-cancellation-policy-row">{{ ui_phrase('Add Rule') }}</button>
    </div>
    <p class="mt-1 text-xs text-gray-500">{{ ui_phrase('Define fee based on days before service date.') }}</p>

    <div class="mt-3 space-y-2" id="cancellation-policy-rows">
        @foreach ($rules as $idx => $rule)
            <div class="grid grid-cols-1 gap-2 rounded border border-gray-200 p-2 md:grid-cols-6 dark:border-gray-700">
                <input type="number" min="0" name="cancellation_rules[{{ $idx }}][min_days_before]" value="{{ $rule['min_days_before'] ?? '' }}" class="app-input" placeholder="{{ ui_phrase('Min Day') }}">
                <input type="number" min="0" name="cancellation_rules[{{ $idx }}][max_days_before]" value="{{ $rule['max_days_before'] ?? '' }}" class="app-input" placeholder="{{ ui_phrase('Max Day') }}">
                <select name="cancellation_rules[{{ $idx }}][fee_type]" class="app-input">
                    <option value="fixed" @selected(($rule['fee_type'] ?? 'fixed') === 'fixed')>{{ ui_phrase('Fixed') }}</option>
                    <option value="percent" @selected(($rule['fee_type'] ?? '') === 'percent')>{{ ui_phrase('Percent') }}</option>
                </select>
                <input type="number" min="0" step="0.01" name="cancellation_rules[{{ $idx }}][fee_value]" value="{{ $rule['fee_value'] ?? '' }}" class="app-input" placeholder="{{ ui_phrase('Fee Value') }}">
                <input type="text" name="cancellation_rules[{{ $idx }}][description]" value="{{ $rule['description'] ?? '' }}" class="app-input md:col-span-2" placeholder="{{ ui_phrase('Description (optional)') }}">
                <div class="md:col-span-6 flex justify-end">
                    <button type="button" class="btn-ghost text-xs remove-cancellation-policy-row">{{ ui_phrase('Remove') }}</button>
                </div>
            </div>
        @endforeach
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const editor = document.getElementById('cancellation-policy-editor');
                if (!editor) return;
                const rows = document.getElementById('cancellation-policy-rows');
                const addBtn = document.getElementById('add-cancellation-policy-row');

                const renumber = () => {
                    Array.from(rows.children).forEach((row, idx) => {
                        row.querySelectorAll('[name]').forEach((input) => {
                            input.name = input.name.replace(/cancellation_rules\[\d+]/, `cancellation_rules[${idx}]`);
                        });
                    });
                };

                const bindRemove = (scope) => {
                    scope.querySelectorAll('.remove-cancellation-policy-row').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            btn.closest('.grid')?.remove();
                            renumber();
                        });
                    });
                };

                addBtn?.addEventListener('click', () => {
                    const idx = rows.children.length;
                    const div = document.createElement('div');
                    div.className = 'grid grid-cols-1 gap-2 rounded border border-gray-200 p-2 md:grid-cols-6 dark:border-gray-700';
                    div.innerHTML = `
                        <input type="number" min="0" name="cancellation_rules[${idx}][min_days_before]" class="app-input" placeholder="Min Day">
                        <input type="number" min="0" name="cancellation_rules[${idx}][max_days_before]" class="app-input" placeholder="Max Day">
                        <select name="cancellation_rules[${idx}][fee_type]" class="app-input">
                            <option value="fixed">Fixed</option>
                            <option value="percent">Percent</option>
                        </select>
                        <input type="number" min="0" step="0.01" name="cancellation_rules[${idx}][fee_value]" class="app-input" placeholder="Fee Value">
                        <input type="text" name="cancellation_rules[${idx}][description]" class="app-input md:col-span-2" placeholder="Description (optional)">
                        <div class="md:col-span-6 flex justify-end">
                            <button type="button" class="btn-ghost text-xs remove-cancellation-policy-row">Remove</button>
                        </div>
                    `;
                    rows.appendChild(div);
                    bindRemove(div);
                });

                bindRemove(editor);
            })();
        </script>
    @endpush
@endonce

