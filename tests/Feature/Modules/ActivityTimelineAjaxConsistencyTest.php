<?php

namespace Tests\Feature\Modules;

use App\Models\Itinerary;

class ActivityTimelineAjaxConsistencyTest extends ModuleSmokeTestCase
{
    public function test_activity_timeline_ajax_fragment_is_consistent_across_modules(): void
    {
        $customer = $this->createCustomer();
        $inquiry = $this->createInquiry($customer);

        $itinerary = Itinerary::query()->create([
            'inquiry_id' => $inquiry->id,
            'created_by' => auth()->id(),
            'title' => 'Timeline Consistency Itinerary',
            'destination' => 'Bandung',
            'duration_days' => 2,
            'duration_nights' => 1,
            'status' => 'pending',
            'is_active' => true,
        ]);

        $quotation = $this->createQuotation($inquiry, [
            'itinerary_id' => $itinerary->id,
            'status' => 'draft',
        ]);

        $routes = [
            route('inquiries.show', $inquiry),
            route('inquiries.edit', $inquiry),
            route('quotations.show', $quotation),
            route('quotations.edit', $quotation),
            route('itineraries.show', $itinerary),
            route('itineraries.edit', $itinerary),
        ];

        foreach ($routes as $url) {
            $this->get($url, [
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Activity-Timeline-Ajax' => '1',
            ])
                ->assertOk()
                ->assertHeader('X-Activity-Timeline-Fragment', '1')
                ->assertSee('data-activity-timeline-panel', false)
                ->assertSee('data-page-spinner="off"', false);
        }
    }
}
