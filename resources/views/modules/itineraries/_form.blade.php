@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $itinerary = $itinerary ?? null;
    $inquiries = $inquiries ?? collect();
    $prefillInquiryId = $prefillInquiryId ?? null;
    $selectedInquiryId = old('inquiry_id', $itinerary->inquiry_id ?? $prefillInquiryId);

    $rawAttractions = old('itinerary_items');
    if (! is_array($rawAttractions)) {
        $rawAttractions = isset($itinerary)
            ? $itinerary->touristAttractions->map(fn ($a) => [
                'tourist_attraction_id' => $a->id,
                'day_number' => $a->pivot->day_number ?? 1,
                'start_time' => $a->pivot->start_time ? substr((string) $a->pivot->start_time, 0, 5) : '',
                'end_time' => $a->pivot->end_time ? substr((string) $a->pivot->end_time, 0, 5) : '',
                'travel_minutes_to_next' => $a->pivot->travel_minutes_to_next ?? null,
                'visit_order' => $a->pivot->visit_order ?? null,
            ])->values()->toArray()
            : [];
    }

    $rawActivities = old('itinerary_activity_items');
    if (! is_array($rawActivities)) {
        $rawActivities = isset($itinerary)
            ? $itinerary->itineraryActivities->map(fn ($a) => [
                'activity_id' => $a->activity_id,
                'pax' => $a->pax ?? 1,
                'day_number' => $a->day_number ?? 1,
                'start_time' => $a->start_time ? substr((string) $a->start_time, 0, 5) : '',
                'end_time' => $a->end_time ? substr((string) $a->end_time, 0, 5) : '',
                'travel_minutes_to_next' => $a->travel_minutes_to_next ?? null,
                'visit_order' => $a->visit_order ?? null,
            ])->values()->toArray()
            : [];
    }

    $durationDays = max(1, (int) old('duration_days', $itinerary->duration_days ?? 1));

    $rows = collect();
    foreach ($rawAttractions as $i => $item) {
        $rows->push([
            'item_type' => 'attraction',
            'tourist_attraction_id' => $item['tourist_attraction_id'] ?? '',
            'activity_id' => '',
            'pax' => 1,
            'day_number' => (int) ($item['day_number'] ?? 1),
            'start_time' => $item['start_time'] ?? '',
            'end_time' => $item['end_time'] ?? '',
            'travel_minutes_to_next' => $item['travel_minutes_to_next'] ?? '',
            'visit_order' => $item['visit_order'] ?? null,
            '_sort' => $i,
        ]);
    }
    foreach ($rawActivities as $i => $item) {
        $rows->push([
            'item_type' => 'activity',
            'tourist_attraction_id' => '',
            'activity_id' => $item['activity_id'] ?? '',
            'pax' => max(1, (int) ($item['pax'] ?? 1)),
            'day_number' => (int) ($item['day_number'] ?? 1),
            'start_time' => $item['start_time'] ?? '',
            'end_time' => $item['end_time'] ?? '',
            'travel_minutes_to_next' => $item['travel_minutes_to_next'] ?? '',
            'visit_order' => $item['visit_order'] ?? null,
            '_sort' => 100000 + $i,
        ]);
    }
    $rowsByDay = $rows->sort(function ($a, $b) {
        if ($a['day_number'] !== $b['day_number']) return $a['day_number'] <=> $b['day_number'];
        if (($a['visit_order'] ?? 999999) !== ($b['visit_order'] ?? 999999)) return ($a['visit_order'] ?? 999999) <=> ($b['visit_order'] ?? 999999);
        return $a['_sort'] <=> $b['_sort'];
    })->groupBy('day_number');

    $inquiryPreviewMap = $inquiries->mapWithKeys(function ($inquiry) {
        return [
            (string) $inquiry->id => [
                'inquiry_number' => (string) ($inquiry->inquiry_number ?? '-'),
                'customer' => trim((string) (($inquiry->customer?->code ? '(' . $inquiry->customer->code . ') ' : '') . ($inquiry->customer?->name ?? '-'))),
                'status' => (string) ($inquiry->status ?? '-'),
                'priority' => (string) ($inquiry->priority ?? '-'),
                'source' => (string) ($inquiry->source ?? '-'),
                'assigned_to' => (string) ($inquiry->assignedUser?->name ?? '-'),
                'deadline' => $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-',
                'created_at' => $inquiry->created_at ? $inquiry->created_at->format('Y-m-d H:i') : '-',
                'notes' => (string) ($inquiry->notes ?? '-'),
            ],
        ];
    });
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Inquiry (Optional)</label>
        <select id="inquiry-select" name="inquiry_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <option value="">Independent itinerary (no inquiry)</option>
            @foreach ($inquiries as $inquiry)
                <option value="{{ $inquiry->id }}" @selected((string) $selectedInquiryId === (string) $inquiry->id)>
                    {{ $inquiry->inquiry_number }}
                    @if (!empty($inquiry->customer?->name))
                        | {{ $inquiry->customer->name }}
                    @endif
                    @if (!empty($inquiry->status))
                        | {{ ucfirst((string) $inquiry->status) }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('inquiry_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
        <input name="title" value="{{ old('title', $itinerary->title ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Duration (Days)</label>
        <input id="duration-days" name="duration_days" type="number" min="1" value="{{ old('duration_days', $itinerary->duration_days ?? 1) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $itinerary->description ?? '') }}</textarea>
    </div>

    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Schedule Items (Attraction + Activity)</p>
        <div id="day-sections" class="space-y-3">
            @for ($day = 1; $day <= $durationDays; $day++)
                @php
                    $dayRows = collect($rowsByDay->get($day, collect()));
                    $dayStart = '';
                    foreach ($dayRows as $r) {
                        if (! empty($r['start_time'])) {
                            $dayStart = substr((string) $r['start_time'], 0, 5);
                            break;
                        }
                    }
                @endphp
                <div class="day-section rounded-xl border border-gray-400 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800" data-day="{{ $day }}">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Day {{ $day }}</p>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-gray-500">Start Tour</label>
                            <input type="time" value="{{ $dayStart }}" class="day-start-time rounded-lg border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="add-attraction rounded-lg border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700">Add Attraction</button>
                            <button type="button" class="add-activity rounded-lg border border-emerald-300 px-3 py-1 text-xs font-medium text-emerald-700">Add Activity</button>
                        </div>
                    </div>
                    <div class="day-items space-y-2">
                        @forelse ($dayRows as $r)
                            <div class="schedule-row grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700 lg:grid-cols-12" data-item-type="{{ $r['item_type'] }}">
                                <div class="flex items-center gap-2 lg:col-span-2">
                                    <button type="button" class="drag-handle inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-base leading-none text-gray-600 dark:border-gray-600 dark:text-gray-300" title="Drag to reorder" aria-label="Drag to reorder">::</button>
                                    <span class="item-seq-badge inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span>
                                </div>
                                <select class="item-type rounded-lg border border-gray-300 px-2 py-2 text-sm lg:col-span-2 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="attraction" @selected($r['item_type'] === 'attraction')>Attraction</option>
                                    <option value="activity" @selected($r['item_type'] === 'activity')>Activity</option>
                                </select>
                                <div class="min-w-0 lg:col-span-8">
                                    <select class="item-attraction w-full rounded-lg border border-gray-300 px-2 py-2 text-sm {{ $r['item_type'] === 'activity' ? 'hidden' : '' }} dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                        <option value="">Select attraction</option>
                                        @foreach ($touristAttractions as $a)
                                            <option value="{{ $a->id }}" data-duration="{{ $a->ideal_visit_minutes ?? 120 }}" data-latitude="{{ $a->latitude }}" data-longitude="{{ $a->longitude }}" @selected((string) ($r['tourist_attraction_id'] ?? '') === (string) $a->id)>{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex flex-col gap-2 sm:flex-row">
                                        <select class="item-activity w-full rounded-lg border border-gray-300 px-2 py-2 text-sm {{ $r['item_type'] === 'attraction' ? 'hidden' : '' }} dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                            <option value="">Select activity</option>
                                            @foreach (($activities ?? collect()) as $a)
                                                <option value="{{ $a->id }}" data-duration="{{ $a->duration_minutes ?? 60 }}" data-latitude="{{ $a->vendor->latitude ?? '' }}" data-longitude="{{ $a->vendor->longitude ?? '' }}" @selected((string) ($r['activity_id'] ?? '') === (string) $a->id)>{{ $a->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" min="1" value="{{ $r['pax'] ?? 1 }}" class="item-pax w-full rounded-lg border border-gray-300 px-2 py-2 text-sm sm:w-24 {{ $r['item_type'] === 'attraction' ? 'hidden' : '' }} dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="Pax">
                                    </div>
                                </div>
                                <input type="time" value="{{ $r['start_time'] ?? '' }}" class="item-start rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm text-gray-700 lg:col-span-3 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" readonly>
                                <input type="time" value="{{ $r['end_time'] ?? '' }}" class="item-end rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm text-gray-700 lg:col-span-3 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" readonly>
                                <button type="button" class="remove-row w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700 lg:col-span-2">Remove</button>
                                <input type="hidden" class="item-travel" value="{{ $r['travel_minutes_to_next'] }}">
                                <input type="hidden" class="item-day" value="{{ $day }}">
                                <input type="hidden" class="item-order" value="{{ $r['visit_order'] ?? '' }}">
                            </div>
                        @empty
                            <div class="schedule-row grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700 lg:grid-cols-12" data-item-type="attraction">
                                <div class="flex items-center gap-2 lg:col-span-2"><button type="button" class="drag-handle inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-base leading-none text-gray-600 dark:border-gray-600 dark:text-gray-300" title="Drag to reorder" aria-label="Drag to reorder">::</button><span class="item-seq-badge inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">-</span></div>
                                <select class="item-type rounded-lg border border-gray-300 px-2 py-2 text-sm lg:col-span-2 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="attraction">Attraction</option><option value="activity">Activity</option></select>
                                <div class="min-w-0 lg:col-span-8">
                                    <select class="item-attraction w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="">Select attraction</option>@foreach ($touristAttractions as $a)<option value="{{ $a->id }}" data-duration="{{ $a->ideal_visit_minutes ?? 120 }}" data-latitude="{{ $a->latitude }}" data-longitude="{{ $a->longitude }}">{{ $a->name }}</option>@endforeach</select>
                                    <div class="flex flex-col gap-2 sm:flex-row"><select class="item-activity hidden w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="">Select activity</option>@foreach (($activities ?? collect()) as $a)<option value="{{ $a->id }}" data-duration="{{ $a->duration_minutes ?? 60 }}" data-latitude="{{ $a->vendor->latitude ?? '' }}" data-longitude="{{ $a->vendor->longitude ?? '' }}">{{ $a->name }}</option>@endforeach</select><input type="number" min="1" value="1" class="item-pax hidden w-full rounded-lg border border-gray-300 px-2 py-2 text-sm sm:w-24 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
                                </div>
                                <input type="time" class="item-start rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm text-gray-700 lg:col-span-3 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" readonly>
                                <input type="time" class="item-end rounded-lg border border-gray-300 bg-gray-100 px-2 py-2 text-sm text-gray-700 lg:col-span-3 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" readonly>
                                <button type="button" class="remove-row w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700 lg:col-span-2">Remove</button>
                                <input type="hidden" class="item-travel" value="">
                                <input type="hidden" class="item-day" value="{{ $day }}"><input type="hidden" class="item-order" value="">
                            </div>
                        @endforelse
                    </div>
                </div>
            @endfor
        </div>
        @error('itinerary_items') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('itinerary_activity_items') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Itinerary Route Preview</p>
        <div id="itinerary-map" class="h-[420px] md:h-[560px] w-full rounded-lg border border-gray-300"></div>
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $itinerary->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>
    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('itineraries.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Cancel</a>
    </div>
</div>

@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    .itinerary-marker-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 9999px;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
    }
    .itinerary-marker-badge.attraction { background: #1d4ed8; }
    .itinerary-marker-badge.activity { background: #059669; }
    .schedule-row-ghost { opacity: 0.4; background: rgba(15, 23, 42, 0.08); }
    .schedule-row-chosen { background: rgba(15, 23, 42, 0.12); }
    .schedule-row { user-select: none; }
    .drag-handle { cursor: grab; }
    .drag-handle:active { cursor: grabbing; }
    .schedule-row input,
    .schedule-row select,
    .schedule-row textarea {
        user-select: text;
    }
    .travel-time-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 9999px;
        background: #111827;
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.28);
    }
    .travel-time-label {
        transform: translate(-50%, -50%);
        pointer-events: none;
        background: transparent;
        border: 0;
    }
    .travel-connector {
        margin: 0.25rem 0 0.5rem;
        border: 1px dashed rgb(203 213 225);
        border-radius: 0.75rem;
        padding: 0.625rem;
        background: rgb(248 250 252);
    }
    .dark .travel-connector {
        border-color: rgb(71 85 105);
        background: rgba(15, 23, 42, 0.35);
    }
</style>
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const inquiryPreviewMap = @json($inquiryPreviewMap);
    const inquirySelect = document.getElementById('inquiry-select');
    const detailEmpty = document.getElementById('inquiry-detail-empty');
    const detailContent = document.getElementById('inquiry-detail-content');
    const detailField = (id) => document.getElementById(id);
    const setDetail = () => {
        if (!inquirySelect || !detailEmpty || !detailContent) return;
        const key = String(inquirySelect.value || '');
        const detail = inquiryPreviewMap[key] || null;
        if (!detail) {
            detailEmpty.classList.remove('hidden');
            detailContent.classList.add('hidden');
            return;
        }
        detailEmpty.classList.add('hidden');
        detailContent.classList.remove('hidden');
        detailField('inq-detail-number').textContent = detail.inquiry_number || '-';
        detailField('inq-detail-customer').textContent = detail.customer || '-';
        detailField('inq-detail-status').textContent = detail.status || '-';
        detailField('inq-detail-priority').textContent = detail.priority || '-';
        detailField('inq-detail-source').textContent = detail.source || '-';
        detailField('inq-detail-assigned').textContent = detail.assigned_to || '-';
        detailField('inq-detail-deadline').textContent = detail.deadline || '-';
        detailField('inq-detail-created').textContent = detail.created_at || '-';
        detailField('inq-detail-notes').textContent = detail.notes || '-';
    };
    inquirySelect?.addEventListener('change', setDetail);
    setDetail();

    const daySections = document.getElementById('day-sections'); const durationInput = document.getElementById('duration-days'); const mapEl = document.getElementById('itinerary-map'); const form = daySections?.closest('form'); if (!daySections || !durationInput || !mapEl || typeof L === 'undefined') return;
    const map = L.map(mapEl).setView([-6.2, 106.816666], 5); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map); const markers = L.layerGroup().addTo(map); const routeLayers = [];
    const toMin = (t) => /^\d{2}:\d{2}$/.test(t || '') ? (parseInt(t.slice(0,2),10)*60)+parseInt(t.slice(3,5),10) : null; const fromMin = (m) => { const n=Math.max(0,Math.min(1439,m)); return `${String(Math.floor(n/60)).padStart(2,'0')}:${String(n%60).padStart(2,'0')}`; };
    const rowType = (r) => r.dataset.itemType === 'activity' ? 'activity' : 'attraction'; const activeSelect = (r) => rowType(r) === 'activity' ? r.querySelector('.item-activity') : r.querySelector('.item-attraction');
    const selected = (r) => (activeSelect(r)?.value || '') !== '';
    const toggleType = (r, t, reset = true) => { const type = t === 'activity' ? 'activity' : 'attraction'; r.dataset.itemType = type; r.querySelector('.item-type').value = type; const a=r.querySelector('.item-attraction'); const b=r.querySelector('.item-activity'); const p=r.querySelector('.item-pax'); if (type === 'activity') { a.classList.add('hidden'); b.classList.remove('hidden'); p.classList.remove('hidden'); if (reset) a.value=''; } else { a.classList.remove('hidden'); b.classList.add('hidden'); p.classList.add('hidden'); if (reset) b.value=''; } };
    const rebuildTravelConnectors = (sec) => {
        const container = sec.querySelector('.day-items');
        if (!container) return;
        container.querySelectorAll('.travel-connector').forEach((el) => el.remove());
        const rows = [...container.querySelectorAll('.schedule-row')];
        rows.forEach((row, index) => {
            const hiddenTravel = row.querySelector('.item-travel');
            if (!hiddenTravel) return;
            const isLast = index === rows.length - 1;
            if (isLast) {
                return;
            }
            const connector = document.createElement('div');
            connector.className = 'travel-connector';
            connector.innerHTML = `
                <label class="block text-xs text-gray-500 dark:text-gray-400">Travel to next item (minutes)</label>
                <input type="number" min="0" step="5" class="travel-connector-input mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            `;
            const input = connector.querySelector('.travel-connector-input');
            input.value = hiddenTravel.value || '';
            input.addEventListener('input', () => {
                const parsed = parseInt(input.value || '', 10);
                hiddenTravel.value = Number.isFinite(parsed) ? String(Math.max(0, parsed)) : '';
                recalcNoConnectorRebuild();
            });
            row.insertAdjacentElement('afterend', connector);
        });
    };
    const recalcDay = async (sec) => { const rows=[...sec.querySelectorAll('.schedule-row')]; const chosen=rows.filter(selected); const start=toMin(sec.querySelector('.day-start-time')?.value || ''); let cur=start; rows.forEach((r)=>{ const seq=r.querySelector('.item-seq-badge'); if(!chosen.includes(r)){ r.querySelector('.item-start').value=''; r.querySelector('.item-end').value=''; r.querySelector('.item-order').value=''; if(seq) seq.textContent='-'; }});
        chosen.forEach((r,i)=>{ r.querySelector('.item-order').value=String(i+1); const seq=r.querySelector('.item-seq-badge'); if(seq) seq.textContent=String(i+1); });
        if (!chosen.length || start===null) return;
        chosen.forEach((r,i)=>{ const opt=activeSelect(r)?.selectedOptions?.[0]; const dur=Math.max(1,parseInt(opt?.dataset?.duration || '120',10)); r.querySelector('.item-start').value=fromMin(cur); r.querySelector('.item-end').value=fromMin(cur+dur); const travel=i<chosen.length-1?Math.max(0,parseInt(r.querySelector('.item-travel').value||'0',10)):0; cur+=dur+travel; });
    };
    const recalcAll = async () => { for (const sec of [...daySections.querySelectorAll('.day-section')].sort((a,b)=>Number(a.dataset.day)-Number(b.dataset.day))) await recalcDay(sec); };
    const reindex = () => { let ai=0, bi=0; [...daySections.querySelectorAll('.day-section')].sort((a,b)=>Number(a.dataset.day)-Number(b.dataset.day)).forEach((sec)=>{ let order=0; const day=Number(sec.dataset.day||'1'); sec.querySelectorAll('.schedule-row').forEach((r)=>{ const a=r.querySelector('.item-attraction'), b=r.querySelector('.item-activity'), p=r.querySelector('.item-pax'), d=r.querySelector('.item-day'), s=r.querySelector('.item-start'), e=r.querySelector('.item-end'), t=r.querySelector('.item-travel'), o=r.querySelector('.item-order'); [a,b,p,d,s,e,t,o].forEach((el)=>el?.removeAttribute('name')); d.value=String(day); if(!selected(r)) return; order+=1; o.value=String(order);
            if(rowType(r)==='activity'){ b.name=`itinerary_activity_items[${bi}][activity_id]`; d.name=`itinerary_activity_items[${bi}][day_number]`; p.name=`itinerary_activity_items[${bi}][pax]`; s.name=`itinerary_activity_items[${bi}][start_time]`; e.name=`itinerary_activity_items[${bi}][end_time]`; t.name=`itinerary_activity_items[${bi}][travel_minutes_to_next]`; o.name=`itinerary_activity_items[${bi}][visit_order]`; bi++; }
            else { a.name=`itinerary_items[${ai}][tourist_attraction_id]`; d.name=`itinerary_items[${ai}][day_number]`; s.name=`itinerary_items[${ai}][start_time]`; e.name=`itinerary_items[${ai}][end_time]`; t.name=`itinerary_items[${ai}][travel_minutes_to_next]`; o.name=`itinerary_items[${ai}][visit_order]`; ai++; } }); }); };
    const badgeIcon = (order, type) => L.divIcon({ className: '', html: `<div class="itinerary-marker-badge ${type}">${order}</div>`, iconSize: [24, 24], iconAnchor: [12, 12] });
    const travelBadgeIcon = (minutes) => L.divIcon({ className: 'travel-time-label', html: `<div class="travel-time-badge">${minutes} m</div>`, iconSize: [0, 0], iconAnchor: [0, 0] });
    const routeColors = ['#2563eb', '#16a34a', '#ea580c', '#db2777', '#7c3aed', '#0891b2'];
    const renderMap = async () => {
        markers.clearLayers();
        routeLayers.forEach((layer) => map.removeLayer(layer));
        routeLayers.length = 0;

        const points=[];
        daySections.querySelectorAll('.schedule-row').forEach((r)=>{ if(!selected(r)) return; const opt=activeSelect(r)?.selectedOptions?.[0]; const lat=parseFloat(opt?.dataset?.latitude||''); const lng=parseFloat(opt?.dataset?.longitude||''); if(!Number.isFinite(lat)||!Number.isFinite(lng)) return; const day=parseInt(r.querySelector('.item-day')?.value||'1',10); const order=parseInt(r.querySelector('.item-order')?.value||'0',10); const travelRaw=r.querySelector('.item-travel')?.value||''; const travelInput=travelRaw!==''?parseInt(travelRaw,10):null; points.push({lat,lng,name:opt.textContent.trim(),type:rowType(r),day,order,travelInput:Number.isFinite(travelInput)?Math.max(0,travelInput):null}); });
        if(!points.length){ map.setView([-6.2,106.816666],5); return; }

        const ll=[];
        const badgeByDay = {};
        points.sort((a,b)=> (a.day-b.day) || (a.order-b.order)).forEach((p,i)=>{ const pt=[p.lat,p.lng]; ll.push(pt); const label=p.type==='activity'?'Activity':'Attraction'; const dayKey=String(p.day); badgeByDay[dayKey]=(badgeByDay[dayKey]||0)+1; const badgeNo=badgeByDay[dayKey]; L.marker(pt, { icon: badgeIcon(badgeNo, p.type) }).bindPopup(`#${badgeNo} | Day ${p.day} | ${label}: ${p.name}`).addTo(markers); });

        const grouped = points.reduce((acc,p)=>{ const key=String(p.day); (acc[key]=acc[key]||[]).push(p); return acc; }, {});
        for (const [dayKey, dayPoints] of Object.entries(grouped)) {
            const sorted = dayPoints.sort((a,b)=>a.order-b.order);
            if (sorted.length < 2) continue;
            const color = routeColors[(parseInt(dayKey,10)-1) % routeColors.length];
            for (let i = 0; i < sorted.length - 1; i++) {
                const from = sorted[i];
                const to = sorted[i + 1];
                const url = `https://router.project-osrm.org/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=full&geometries=geojson`;
                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    const route = data?.routes?.[0];
                    const geometry = route?.geometry;
                    const minutesFromApi = Number.isFinite(route?.duration) ? Math.max(1, Math.round(route.duration / 60)) : null;
                    const labelMinutes = from.travelInput !== null ? from.travelInput : minutesFromApi;

                    if (geometry && Array.isArray(geometry.coordinates) && geometry.coordinates.length > 1) {
                        const layer = L.geoJSON(geometry, { style: { color, weight: 3, opacity: 0.85 } }).addTo(map);
                        routeLayers.push(layer);

                        if (labelMinutes !== null) {
                            const mid = geometry.coordinates[Math.floor(geometry.coordinates.length / 2)];
                            const badge = L.marker([mid[1], mid[0]], { icon: travelBadgeIcon(labelMinutes), interactive: false }).addTo(map);
                            routeLayers.push(badge);
                        }
                    } else {
                        const fallbackPoints = [[from.lat, from.lng], [to.lat, to.lng]];
                        const fallback = L.polyline(fallbackPoints, { color, weight: 3, opacity: 0.8 }).addTo(map);
                        routeLayers.push(fallback);
                        if (labelMinutes !== null) {
                            const midLat = (from.lat + to.lat) / 2;
                            const midLng = (from.lng + to.lng) / 2;
                            const badge = L.marker([midLat, midLng], { icon: travelBadgeIcon(labelMinutes), interactive: false }).addTo(map);
                            routeLayers.push(badge);
                        }
                    }
                } catch (_) {
                    const fallbackPoints = [[from.lat, from.lng], [to.lat, to.lng]];
                    const fallback = L.polyline(fallbackPoints, { color, weight: 3, opacity: 0.8 }).addTo(map);
                    routeLayers.push(fallback);
                    if (from.travelInput !== null) {
                        const midLat = (from.lat + to.lat) / 2;
                        const midLng = (from.lng + to.lng) / 2;
                        const badge = L.marker([midLat, midLng], { icon: travelBadgeIcon(from.travelInput), interactive: false }).addTo(map);
                        routeLayers.push(badge);
                    }
                }
            }
        }

        if(ll.length===1) map.setView(ll[0],14); else map.fitBounds(ll,{padding:[20,20]});
    };
    const recalcNoConnectorRebuild = async () => { await recalcAll(); reindex(); await renderMap(); };
    const recalc = async () => { daySections.querySelectorAll('.day-section').forEach(rebuildTravelConnectors); await recalcNoConnectorRebuild(); };
    const initSortable = (sec) => {
        const container = sec.querySelector('.day-items');
        if (!container || container.dataset.sortableInit || typeof Sortable === 'undefined') return;
        Sortable.create(container, {
            group: {
                name: 'itinerary-day-items',
                pull: true,
                put: true,
            },
            animation: 200,
            forceFallback: true,
            fallbackTolerance: 3,
            draggable: '.schedule-row',
            handle: '.drag-handle',
            ghostClass: 'schedule-row-ghost',
            chosenClass: 'schedule-row-chosen',
            onEnd: () => recalc(),
        });
        container.dataset.sortableInit = '1';
    };
    const bindRow = (r) => { r.querySelector('.item-type')?.addEventListener('change',(e)=>{ toggleType(r,e.target.value,true); recalc(); }); r.querySelector('.item-attraction')?.addEventListener('change',recalc); r.querySelector('.item-activity')?.addEventListener('change',recalc); r.querySelector('.item-pax')?.addEventListener('change',reindex); r.querySelector('.remove-row')?.addEventListener('click',()=>{ if(daySections.querySelectorAll('.schedule-row').length<=1) return; r.remove(); recalc(); }); toggleType(r,rowType(r),false); };
    const cloneRow = (sec, type) => { const src=sec.querySelector('.schedule-row'); if(!src) return; const r=src.cloneNode(true); r.querySelector('.item-attraction').value=''; r.querySelector('.item-activity').value=''; r.querySelector('.item-pax').value='1'; r.querySelector('.item-start').value=''; r.querySelector('.item-end').value=''; r.querySelector('.item-travel').value=''; r.querySelector('.item-order').value=''; const seq=r.querySelector('.item-seq-badge'); if(seq) seq.textContent='-'; sec.querySelector('.day-items').appendChild(r); bindRow(r); toggleType(r,type,false); recalc(); };
    daySections.querySelectorAll('.day-section').forEach((sec)=>{ sec.querySelectorAll('.schedule-row').forEach(bindRow); sec.querySelector('.add-attraction')?.addEventListener('click',()=>cloneRow(sec,'attraction')); sec.querySelector('.add-activity')?.addEventListener('click',()=>cloneRow(sec,'activity')); sec.querySelector('.day-start-time')?.addEventListener('change',recalc); initSortable(sec); });
    durationInput.addEventListener('change',()=>{ let d=Math.max(1,parseInt(durationInput.value||'1',10)); durationInput.value=String(d); let secs=[...daySections.querySelectorAll('.day-section')]; for(let i=1;i<=d;i++){ if(!daySections.querySelector(`.day-section[data-day="${i}"]`) && secs.length){ const c=secs[0].cloneNode(true); c.dataset.day=String(i); c.querySelector('.text-indigo-700').textContent=`Day ${i}`; c.querySelector('.day-start-time').value=''; c.querySelectorAll('.travel-connector').forEach((el)=>el.remove()); const rows=[...c.querySelectorAll('.schedule-row')]; rows.slice(1).forEach((r)=>r.remove()); const r=c.querySelector('.schedule-row'); if(r){ r.dataset.itemType='attraction'; r.querySelector('.item-type').value='attraction'; r.querySelector('.item-attraction').value=''; r.querySelector('.item-activity').value=''; r.querySelector('.item-pax').value='1'; r.querySelector('.item-start').value=''; r.querySelector('.item-end').value=''; r.querySelector('.item-travel').value=''; r.querySelector('.item-day').value=String(i); r.querySelector('.item-order').value=''; const seq=r.querySelector('.item-seq-badge'); if(seq) seq.textContent='-'; } const dayItems = c.querySelector('.day-items'); if (dayItems) delete dayItems.dataset.sortableInit; daySections.appendChild(c); c.querySelectorAll('.schedule-row').forEach(bindRow); c.querySelector('.add-attraction')?.addEventListener('click',()=>cloneRow(c,'attraction')); c.querySelector('.add-activity')?.addEventListener('click',()=>cloneRow(c,'activity')); c.querySelector('.day-start-time')?.addEventListener('change',recalc); initSortable(c);} } [...daySections.querySelectorAll('.day-section')].forEach((s)=>{ if(Number(s.dataset.day)>d) s.remove(); }); recalc(); });
    form?.addEventListener('submit', async (e) => { e.preventDefault(); await recalcAll(); reindex(); const hasA=[...daySections.querySelectorAll('.schedule-row')].some((r)=>rowType(r)==='attraction'&&selected(r)); if(!hasA){ alert('Minimal 1 attraction wajib diisi.'); return; } form.submit(); });
    recalc();
})();
</script>
@endpush
@endonce
