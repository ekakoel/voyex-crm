<?php

namespace App\Support;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;

class CompanySettingsCache
{
    private const CACHE_KEY = 'company_settings:view_data:v1';
    private const CACHE_TTL_SECONDS = 300;

    /**
     * @return \App\Models\CompanySetting|null
     */
    public static function get(): ?CompanySetting
    {
        if (! SchemaInspector::hasTable('company_settings')) {
            return null;
        }

        return Cache::remember(self::CACHE_KEY, now()->addSeconds(self::CACHE_TTL_SECONDS), function (): ?CompanySetting {
            $columns = ['company_name', 'footer_note', 'updated_at'];

            foreach ([
                'tagline',
                'logo_path',
                'favicon_path',
                'auth_primary_color',
                'auth_primary_hover_color',
                'auth_background_from_color',
                'auth_background_to_color',
                'auth_card_background_color',
                'auth_card_border_color',
            ] as $column) {
                if (SchemaInspector::hasColumn('company_settings', $column)) {
                    $columns[] = $column;
                }
            }

            return CompanySetting::query()->first($columns);
        });
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
