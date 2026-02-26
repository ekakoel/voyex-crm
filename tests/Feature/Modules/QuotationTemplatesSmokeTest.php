<?php

namespace Tests\Feature\Modules;

use App\Models\QuotationTemplate;
use Illuminate\Support\Str;

class QuotationTemplatesSmokeTest extends ModuleSmokeTestCase
{
    public function test_quotation_templates_get_and_store_smoke(): void
    {
        $this->get(route('quotation-templates.index'))->assertOk();
        $this->get(route('quotation-templates.create'))->assertOk();

        $name = 'Smoke Template ' . Str::upper(Str::random(5));
        $this->post(route('quotation-templates.store'), [
            'name' => $name,
            'body_html' => '<p>Smoke template body</p>',
            'is_active' => 1,
        ])->assertRedirect(route('quotation-templates.index'));

        $template = QuotationTemplate::query()->where('name', $name)->latest()->firstOrFail();
        $this->get(route('quotation-templates.edit', $template))->assertOk();
    }
}

