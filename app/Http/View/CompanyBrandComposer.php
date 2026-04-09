<?php

namespace App\Http\View;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyBrandComposer
{
    public function compose(View $view): void
    {
        $companyName = config('app.name', 'VOYEX CRM');
        $companyTagline = 'Smart Travel CRM Platform';
        $companyFooterNote = '';

        try {
            if (Schema::hasTable('company_settings')) {
                $columns = ['company_name', 'footer_note'];
                if (Schema::hasColumn('company_settings', 'tagline')) {
                    $columns[] = 'tagline';
                }

                $settings = CompanySetting::query()->first($columns);
                if ($settings) {
                    $companyName = $this->decodeHtmlEntitiesDeep((string) ($settings->company_name ?: $companyName));
                    $companyTagline = $this->decodeHtmlEntitiesDeep((string) ($settings->tagline ?? '')) ?: $companyTagline;
                    $companyFooterNote = $this->decodeHtmlEntitiesDeep((string) ($settings->footer_note ?? ''));
                }
            }
        } catch (\Throwable $e) {
            // Fallback to app name when settings table is not ready.
        }

        $view->with('companyName', $companyName);
        $view->with('companyTagline', $companyTagline);
        $view->with('companyFooterNote', $companyFooterNote);
    }

    private function decodeHtmlEntitiesDeep(string $value): string
    {
        $decoded = trim($value);
        for ($i = 0; $i < 3; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        return trim($decoded);
    }
}
