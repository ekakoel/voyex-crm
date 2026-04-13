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
        $companyLogoUrl = null;
        $companyFaviconUrl = null;
        $companyFaviconMime = null;

        $authPrimaryColor = '#2563eb';
        $authPrimaryHoverColor = '#1e40af';
        $authBackgroundFromColor = '#f5f7fb';
        $authBackgroundToColor = '#eaf1ff';
        $authCardBackgroundColor = '#ffffff';
        $authCardBorderColor = '#d7d7d7';

        try {
            if (Schema::hasTable('company_settings')) {
                $columns = ['company_name', 'footer_note', 'updated_at'];
                if (Schema::hasColumn('company_settings', 'tagline')) {
                    $columns[] = 'tagline';
                }
                if (Schema::hasColumn('company_settings', 'logo_path')) {
                    $columns[] = 'logo_path';
                }
                if (Schema::hasColumn('company_settings', 'favicon_path')) {
                    $columns[] = 'favicon_path';
                }
                if (Schema::hasColumn('company_settings', 'auth_primary_color')) {
                    $columns[] = 'auth_primary_color';
                }
                if (Schema::hasColumn('company_settings', 'auth_primary_hover_color')) {
                    $columns[] = 'auth_primary_hover_color';
                }
                if (Schema::hasColumn('company_settings', 'auth_background_from_color')) {
                    $columns[] = 'auth_background_from_color';
                }
                if (Schema::hasColumn('company_settings', 'auth_background_to_color')) {
                    $columns[] = 'auth_background_to_color';
                }
                if (Schema::hasColumn('company_settings', 'auth_card_background_color')) {
                    $columns[] = 'auth_card_background_color';
                }
                if (Schema::hasColumn('company_settings', 'auth_card_border_color')) {
                    $columns[] = 'auth_card_border_color';
                }

                $settings = CompanySetting::query()->first($columns);
                if ($settings) {
                    $companyName = $this->decodeHtmlEntitiesDeep((string) ($settings->company_name ?: $companyName));
                    $companyTagline = $this->decodeHtmlEntitiesDeep((string) ($settings->tagline ?? '')) ?: $companyTagline;
                    $companyFooterNote = $this->decodeHtmlEntitiesDeep((string) ($settings->footer_note ?? ''));

                    $version = !empty($settings->updated_at) ? $settings->updated_at->timestamp : null;
                    $logoPath = (string) ($settings->logo_path ?? '');
                    $faviconPath = (string) ($settings->favicon_path ?? '');
                    if ($logoPath !== '') {
                        $companyLogoUrl = asset('storage/' . ltrim($logoPath, '/')) . ($version ? ('?v=' . $version) : '');
                    }
                    if ($faviconPath !== '') {
                        $companyFaviconUrl = asset('storage/' . ltrim($faviconPath, '/')) . ($version ? ('?v=' . $version) : '');
                        $companyFaviconMime = $this->resolveFaviconMime($faviconPath);
                    }

                    $authPrimaryColor = $this->safeHexColor((string) ($settings->auth_primary_color ?? ''), $authPrimaryColor);
                    $authPrimaryHoverColor = $this->safeHexColor((string) ($settings->auth_primary_hover_color ?? ''), $authPrimaryHoverColor);
                    $authBackgroundFromColor = $this->safeHexColor((string) ($settings->auth_background_from_color ?? ''), $authBackgroundFromColor);
                    $authBackgroundToColor = $this->safeHexColor((string) ($settings->auth_background_to_color ?? ''), $authBackgroundToColor);
                    $authCardBackgroundColor = $this->safeHexColor((string) ($settings->auth_card_background_color ?? ''), $authCardBackgroundColor);
                    $authCardBorderColor = $this->safeHexColor((string) ($settings->auth_card_border_color ?? ''), $authCardBorderColor);
                }
            }
        } catch (\Throwable $e) {
            // Fallback to app name when settings table is not ready.
        }

        $view->with('companyName', $companyName);
        $view->with('companyTagline', $companyTagline);
        $view->with('companyFooterNote', $companyFooterNote);
        $view->with('companyLogoUrl', $companyLogoUrl);
        $view->with('companyFaviconUrl', $companyFaviconUrl);
        $view->with('companyFaviconMime', $companyFaviconMime);
        $view->with('authPrimaryColor', $authPrimaryColor);
        $view->with('authPrimaryHoverColor', $authPrimaryHoverColor);
        $view->with('authBackgroundFromColor', $authBackgroundFromColor);
        $view->with('authBackgroundToColor', $authBackgroundToColor);
        $view->with('authCardBackgroundColor', $authCardBackgroundColor);
        $view->with('authCardBorderColor', $authCardBorderColor);
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

    private function safeHexColor(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        if (preg_match('/^#([0-9a-f]{6})$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    private function resolveFaviconMime(string $faviconPath): string
    {
        $ext = strtolower((string) pathinfo($faviconPath, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/x-icon',
        };
    }
}
