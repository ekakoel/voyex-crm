<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        // Retrieve all settings and clean them
        $settings = DB::table('company_settings')->get();

        foreach ($settings as $setting) {
            $updates = [];

            // Clean company_name
            if (! empty($setting->company_name)) {
                $cleaned = $this->cleanHtmlEntities($setting->company_name);
                if ($cleaned !== $setting->company_name) {
                    $updates['company_name'] = $cleaned;
                }
            }

            // Clean tagline
            if (! empty($setting->tagline)) {
                $cleaned = $this->cleanHtmlEntities($setting->tagline);
                if ($cleaned !== $setting->tagline) {
                    $updates['tagline'] = $cleaned;
                }
            }

            // Clean footer_note
            if (! empty($setting->footer_note)) {
                $cleaned = $this->cleanHtmlEntities($setting->footer_note);
                if ($cleaned !== $setting->footer_note) {
                    $updates['footer_note'] = $cleaned;
                }
            }

            // Clean legal_name
            if (! empty($setting->legal_name)) {
                $cleaned = $this->cleanHtmlEntities($setting->legal_name);
                if ($cleaned !== $setting->legal_name) {
                    $updates['legal_name'] = $cleaned;
                }
            }

            // Clean address
            if (! empty($setting->address)) {
                $cleaned = $this->cleanHtmlEntities($setting->address);
                if ($cleaned !== $setting->address) {
                    $updates['address'] = $cleaned;
                }
            }

            if (! empty($updates)) {
                DB::table('company_settings')
                    ->where('id', $setting->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback is not reversible; data cannot be recovered automatically
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
};
