<?php

namespace App\Http\View;

use App\Support\CompanySettingsCache;
use App\Support\ImageThumbnailGenerator;
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
            $settings = CompanySettingsCache::get();
            if ($settings) {
                $companyName = $this->cleanHtmlEntities((string) ($settings->company_name ?: $companyName));
                $companyTagline = $this->cleanHtmlEntities((string) ($settings->tagline ?? '')) ?: $companyTagline;
                $companyFooterNote = $this->cleanHtmlEntities((string) ($settings->footer_note ?? ''));

                $version = !empty($settings->updated_at) ? $settings->updated_at->timestamp : null;
                $logoPath = (string) ($settings->logo_path ?? '');
                $faviconPath = (string) ($settings->favicon_path ?? '');
                if ($logoPath !== '') {
                    $companyLogoUrl = ImageThumbnailGenerator::resolvePublicUrl($logoPath)
                        ?? ImageThumbnailGenerator::resolveOriginalPublicUrl($logoPath);
                    if ($companyLogoUrl !== null && $version) {
                        $companyLogoUrl .= '?v=' . $version;
                    }
                }
                if ($faviconPath !== '') {
                    $companyFaviconUrl = ImageThumbnailGenerator::resolveOriginalPublicUrl($faviconPath);
                    if ($companyFaviconUrl !== null && $version) {
                        $companyFaviconUrl .= '?v=' . $version;
                    }
                    $companyFaviconMime = $this->resolveFaviconMime($faviconPath);
                }

                $authPrimaryColor = $this->safeHexColor((string) ($settings->auth_primary_color ?? ''), $authPrimaryColor);
                $authPrimaryHoverColor = $this->safeHexColor((string) ($settings->auth_primary_hover_color ?? ''), $authPrimaryHoverColor);
                $authBackgroundFromColor = $this->safeHexColor((string) ($settings->auth_background_from_color ?? ''), $authBackgroundFromColor);
                $authBackgroundToColor = $this->safeHexColor((string) ($settings->auth_background_to_color ?? ''), $authBackgroundToColor);
                $authCardBackgroundColor = $this->safeHexColor((string) ($settings->auth_card_background_color ?? ''), $authCardBackgroundColor);
                $authCardBorderColor = $this->safeHexColor((string) ($settings->auth_card_border_color ?? ''), $authCardBorderColor);
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

    private function cleanHtmlEntities(string $value): string
    {
        $value = trim($value);
        
        // First, decode HTML entities once
        $cleaned = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove excessive ampersand encoding (common issue with &amp;amp;...)
        // Keep doing this while we find excessive encoding
        while (strpos($cleaned, '&amp;amp;') !== false || strpos($cleaned, '&#38;') !== false) {
            $prev = $cleaned;
            $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // If nothing changed, break to avoid infinite loop
            if ($cleaned === $prev) {
                break;
            }
        }
        
        return trim($cleaned);
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
