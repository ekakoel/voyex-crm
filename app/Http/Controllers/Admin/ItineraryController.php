<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\TouristAttraction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ItineraryController extends Controller
{
    public function index()
    {
        $itineraries = Itinerary::query()
            ->with([
                'touristAttractions:id,name',
                'inquiry:id,inquiry_number,customer_id',
                'inquiry.customer:id,name',
            ])
            ->orderBy('title')
            ->paginate(10);

        return view('modules.itineraries.index', compact('itineraries'));
    }

    public function create(Request $request)
    {
        $touristAttractions = TouristAttraction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'agent_price', 'currency']);
        $inquiries = Inquiry::query()
            ->with([
                'customer:id,name,code',
                'assignedUser:id,name',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'inquiry_number',
                'customer_id',
                'source',
                'status',
                'priority',
                'assigned_to',
                'deadline',
                'notes',
                'created_at',
            ]);
        $prefillInquiryId = $request->integer('inquiry_id') ?: null;

        return view('modules.itineraries.create', compact('touristAttractions', 'activities', 'inquiries', 'prefillInquiryId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'inquiry_id' => ['nullable', 'integer', 'exists:inquiries,id'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'itinerary_items' => ['required', 'array', 'min:1'],
            'itinerary_items.*.tourist_attraction_id' => ['required', 'integer', 'distinct', 'exists:tourist_attractions,id'],
            'itinerary_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_activity_items' => ['nullable', 'array'],
            'itinerary_activity_items.*.activity_id' => ['required', 'integer', 'exists:activities,id'],
            'itinerary_activity_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_activity_items.*.pax' => ['required', 'integer', 'min:1'],
            'itinerary_activity_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $items = $validated['itinerary_items'];
        $activityItems = $validated['itinerary_activity_items'] ?? [];
        unset($validated['itinerary_items']);
        unset($validated['itinerary_activity_items']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);

        $itinerary = Itinerary::query()->create($validated);
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);

        return redirect()->route('itineraries.index')->with('success', 'Itinerary created successfully.');
    }

    public function edit(Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,agent_price,currency',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'inquiry:id,inquiry_number,customer_id',
        ]);
        $touristAttractions = TouristAttraction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'city', 'province', 'latitude', 'longitude', 'ideal_visit_minutes']);
        $activities = Activity::query()
            ->with('vendor:id,name,city,province,location,latitude,longitude')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'activity_type', 'duration_minutes', 'agent_price', 'currency']);
        $inquiries = Inquiry::query()
            ->with([
                'customer:id,name,code',
                'assignedUser:id,name',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'inquiry_number',
                'customer_id',
                'source',
                'status',
                'priority',
                'assigned_to',
                'deadline',
                'notes',
                'created_at',
            ]);

        return view('modules.itineraries.edit', compact('itinerary', 'touristAttractions', 'activities', 'inquiries'));
    }

    public function show(Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,agent_price,currency,includes,benefits,notes',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'inquiry:id,inquiry_number,customer_id',
            'inquiry.customer:id,name',
        ]);
        $dayGroups = $itinerary->touristAttractions->groupBy(fn ($attraction) => (int) $attraction->pivot->day_number);
        $activityDayGroups = $itinerary->itineraryActivities->groupBy(fn ($item) => (int) $item->day_number);

        return view('modules.itineraries.show', compact('itinerary', 'dayGroups', 'activityDayGroups'));
    }

    public function generatePdf(Request $request, Itinerary $itinerary)
    {
        $itinerary->load([
            'touristAttractions:id,name,location,latitude,longitude,description',
            'itineraryActivities.activity:id,vendor_id,name,activity_type,duration_minutes,agent_price,currency,notes',
            'itineraryActivities.activity.vendor:id,name,location,city,province,latitude,longitude',
            'inquiry:id,inquiry_number,customer_id,status,priority,source,deadline,notes',
            'inquiry.customer:id,name,code',
        ]);

        $scheduleByDay = [];
        for ($day = 1; $day <= (int) $itinerary->duration_days; $day++) {
            $attractions = $itinerary->touristAttractions
                ->filter(fn ($attraction) => (int) ($attraction->pivot->day_number ?? 0) === $day)
                ->map(function ($attraction) {
                    return [
                        'type' => 'Attraction',
                        'name' => (string) $attraction->name,
                        'location' => (string) ($attraction->location ?? '-'),
                        'description' => (string) ($attraction->description ?? '-'),
                        'pax' => null,
                        'start_time' => $attraction->pivot->start_time ? substr((string) $attraction->pivot->start_time, 0, 5) : '--:--',
                        'end_time' => $attraction->pivot->end_time ? substr((string) $attraction->pivot->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $attraction->pivot->travel_minutes_to_next,
                        'visit_order' => (int) ($attraction->pivot->visit_order ?? 999999),
                    ];
                });

            $activities = $itinerary->itineraryActivities
                ->filter(fn ($item) => (int) ($item->day_number ?? 0) === $day)
                ->map(function ($item) {
                    return [
                        'type' => 'Activity',
                        'name' => (string) ($item->activity->name ?? '-'),
                        'location' => (string) ($item->activity->vendor->location ?? '-'),
                        'description' => (string) ($item->activity->notes ?? '-'),
                        'pax' => (int) ($item->pax ?? 0),
                        'start_time' => $item->start_time ? substr((string) $item->start_time, 0, 5) : '--:--',
                        'end_time' => $item->end_time ? substr((string) $item->end_time, 0, 5) : '--:--',
                        'travel_minutes_to_next' => $item->travel_minutes_to_next,
                        'visit_order' => (int) ($item->visit_order ?? 999999),
                    ];
                });

            $items = $attractions->merge($activities)->sortBy('visit_order')->values();
            $startTime = $items->pluck('start_time')->filter(fn ($time) => $time !== '--:--')->first() ?? '--:--';
            $endTime = $items->pluck('end_time')->filter(fn ($time) => $time !== '--:--')->last() ?? '--:--';

            $scheduleByDay[] = [
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'items' => $items,
            ];
        }

        $pdf = Pdf::loadView('pdf.itinerary', [
            'itinerary' => $itinerary,
            'scheduleByDay' => $scheduleByDay,
            'companyName' => (string) config('app.name', 'Voyex CRM'),
            'companyTagline' => (string) env('COMPANY_TAGLINE', 'Travel Itinerary & Experience Planner'),
            'companyLogoDataUri' => $this->resolveCompanyLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'itinerary-' . Str::slug($itinerary->title ?: 'untitled') . '.pdf';
        $mode = strtolower((string) $request->query('mode', 'download'));
        if ($mode === 'stream') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    private function resolveCompanyLogoDataUri(): string
    {
        $candidates = [
            public_path('images/company-logo.png'),
            public_path('images/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($candidates as $path) {
            if (! File::exists($path)) {
                continue;
            }
            $binary = File::get($path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $name = (string) config('app.name', 'VOYEX');
        $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3) ?: 'VYX');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="360" height="90" viewBox="0 0 360 90">
  <rect width="360" height="90" rx="14" fill="#0f172a"/>
  <circle cx="48" cy="45" r="24" fill="#1d4ed8"/>
  <text x="48" y="51" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" font-weight="700" fill="#ffffff">{$initials}</text>
  <text x="86" y="40" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#ffffff">{$name}</text>
  <text x="86" y="60" font-family="Arial, sans-serif" font-size="11" fill="#cbd5e1">Professional Itinerary</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function update(Request $request, Itinerary $itinerary)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'inquiry_id' => ['nullable', 'integer', 'exists:inquiries,id'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'itinerary_items' => ['required', 'array', 'min:1'],
            'itinerary_items.*.tourist_attraction_id' => ['required', 'integer', 'distinct', 'exists:tourist_attractions,id'],
            'itinerary_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
            'itinerary_activity_items' => ['nullable', 'array'],
            'itinerary_activity_items.*.activity_id' => ['required', 'integer', 'exists:activities,id'],
            'itinerary_activity_items.*.day_number' => ['required', 'integer', 'min:1'],
            'itinerary_activity_items.*.pax' => ['required', 'integer', 'min:1'],
            'itinerary_activity_items.*.start_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.end_time' => ['nullable', 'date_format:H:i'],
            'itinerary_activity_items.*.travel_minutes_to_next' => ['nullable', 'integer', 'min:0'],
            'itinerary_activity_items.*.visit_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $items = $validated['itinerary_items'];
        $activityItems = $validated['itinerary_activity_items'] ?? [];
        unset($validated['itinerary_items']);
        unset($validated['itinerary_activity_items']);
        $this->validateScheduleItems($items, (int) $validated['duration_days']);
        $this->validateActivityItems($activityItems, (int) $validated['duration_days']);

        $itinerary->update($validated);
        $itinerary->touristAttractions()->sync($this->buildSyncPayload($items));
        $this->syncItineraryActivities($itinerary, $activityItems);

        return redirect()->route('itineraries.index')->with('success', 'Itinerary updated successfully.');
    }

    public function destroy(Itinerary $itinerary)
    {
        $itinerary->delete();

        return redirect()->route('itineraries.index')->with('success', 'Itinerary deleted successfully.');
    }

    private function validateScheduleItems(array $items, int $durationDays): void
    {
        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $day = (int) ($item['day_number'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "itinerary_items.{$index}.day_number" => "Day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
        }
    }

    private function buildSyncPayload(array $items): array
    {
        $items = $this->sortScheduleItems($items);
        $durationByAttraction = TouristAttraction::query()
            ->whereIn('id', array_column($items, 'tourist_attraction_id'))
            ->pluck('ideal_visit_minutes', 'id');

        $syncData = [];
        $fallbackVisitOrderByDay = [];

        foreach (array_values($items) as $item) {
            $dayNumber = (int) $item['day_number'];
            $fallbackVisitOrderByDay[$dayNumber] = ($fallbackVisitOrderByDay[$dayNumber] ?? 0) + 1;
            $startTime = $item['start_time'] ?: null;
            $idealMinutes = (int) ($durationByAttraction[(int) $item['tourist_attraction_id']] ?? 120);
            $manualEndTime = $item['end_time'] ?: null;
            $travelMinutes = isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                ? max(0, (int) $item['travel_minutes_to_next'])
                : null;
            $visitOrder = isset($item['visit_order']) && $item['visit_order'] !== ''
                ? max(1, (int) $item['visit_order'])
                : $fallbackVisitOrderByDay[$dayNumber];

            $syncData[(int) $item['tourist_attraction_id']] = [
                'day_number' => $dayNumber,
                'start_time' => $startTime,
                'end_time' => $manualEndTime ?: ($startTime ? $this->calculateEndTimeFromIdealDuration($startTime, $idealMinutes) : null),
                'travel_minutes_to_next' => $travelMinutes,
                'visit_order' => $visitOrder,
            ];
        }

        return $syncData;
    }

    private function sortScheduleItems(array $items): array
    {
        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            $normalized[] = [
                'tourist_attraction_id' => (int) $item['tourist_attraction_id'],
                'day_number' => (int) $item['day_number'],
                'start_time' => $item['start_time'] ?: null,
                'end_time' => $item['end_time'] ?: null,
                'travel_minutes_to_next' => isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                    ? max(0, (int) $item['travel_minutes_to_next'])
                    : null,
                'visit_order' => isset($item['visit_order']) && $item['visit_order'] !== ''
                    ? max(1, (int) $item['visit_order'])
                    : null,
                '_row_order' => $index,
            ];
        }

        usort($normalized, function (array $a, array $b): int {
            if ($a['day_number'] !== $b['day_number']) {
                return $a['day_number'] <=> $b['day_number'];
            }

            $aHasVisitOrder = $a['visit_order'] !== null;
            $bHasVisitOrder = $b['visit_order'] !== null;
            if ($aHasVisitOrder !== $bHasVisitOrder) {
                return $aHasVisitOrder ? -1 : 1;
            }
            if ($a['visit_order'] !== $b['visit_order']) {
                return ($a['visit_order'] ?? 999999) <=> ($b['visit_order'] ?? 999999);
            }

            $aHasStart = $a['start_time'] !== null;
            $bHasStart = $b['start_time'] !== null;
            if ($aHasStart !== $bHasStart) {
                return $aHasStart ? -1 : 1;
            }

            if ($a['start_time'] !== $b['start_time']) {
                return ($a['start_time'] ?? '99:99') <=> ($b['start_time'] ?? '99:99');
            }

            return $a['_row_order'] <=> $b['_row_order'];
        });

        return array_map(function (array $item): array {
            unset($item['_row_order']);
            return $item;
        }, $normalized);
    }

    private function calculateEndTimeFromIdealDuration(string $startTime, int $idealMinutes): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $startTime));
        $startTotal = ($hour * 60) + $minute;
        $endTotal = $startTotal + max(1, $idealMinutes);

        if ($endTotal > 1439) {
            $endTotal = 1439;
        }

        $endHour = intdiv($endTotal, 60);
        $endMinute = $endTotal % 60;

        return sprintf('%02d:%02d', $endHour, $endMinute);
    }

    private function validateActivityItems(array $items, int $durationDays): void
    {
        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $day = (int) ($item['day_number'] ?? 0);
            if ($day > $durationDays) {
                throw ValidationException::withMessages([
                    "itinerary_activity_items.{$index}.day_number" => "Activity day on row {$rowNumber} cannot exceed itinerary duration.",
                ]);
            }
        }
    }

    private function syncItineraryActivities(Itinerary $itinerary, array $items): void
    {
        $itinerary->itineraryActivities()->delete();
        if ($items === []) {
            return;
        }

        $activityDurations = Activity::query()
            ->whereIn('id', array_column($items, 'activity_id'))
            ->pluck('duration_minutes', 'id');

        $payload = [];
        $fallbackVisitOrderByDay = [];
        foreach (array_values($items) as $item) {
            $dayNumber = (int) $item['day_number'];
            $fallbackVisitOrderByDay[$dayNumber] = ($fallbackVisitOrderByDay[$dayNumber] ?? 0) + 1;
            $activityId = (int) $item['activity_id'];
            $startTime = $item['start_time'] ?: null;
            $durationMinutes = (int) ($activityDurations[$activityId] ?? 60);
            $manualEndTime = $item['end_time'] ?: null;
            $travelMinutes = isset($item['travel_minutes_to_next']) && $item['travel_minutes_to_next'] !== ''
                ? max(0, (int) $item['travel_minutes_to_next'])
                : null;
            $visitOrder = isset($item['visit_order']) && $item['visit_order'] !== ''
                ? max(1, (int) $item['visit_order'])
                : $fallbackVisitOrderByDay[$dayNumber];

            $payload[] = [
                'itinerary_id' => $itinerary->id,
                'activity_id' => $activityId,
                'day_number' => $dayNumber,
                'pax' => (int) $item['pax'],
                'start_time' => $startTime,
                'end_time' => $manualEndTime ?: ($startTime ? $this->calculateTimeFromDuration($startTime, $durationMinutes) : null),
                'travel_minutes_to_next' => $travelMinutes,
                'visit_order' => $visitOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $itinerary->itineraryActivities()->insert($payload);
    }

    private function calculateTimeFromDuration(string $startTime, int $durationMinutes): string
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        return $start->addMinutes(max(1, $durationMinutes))->format('H:i');
    }

}



