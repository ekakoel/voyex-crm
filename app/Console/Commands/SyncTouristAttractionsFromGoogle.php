<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Services\Maps\GooglePlacesService;
use App\Services\TouristAttractionGoogleSyncService;
use Illuminate\Console\Command;

class SyncTouristAttractionsFromGoogle extends Command
{
    protected $signature = 'tourist-attractions:sync-google
        {--destination_id= : Sync only one destination ID}
        {--all-destinations : Sync all active destinations}
        {--query= : Custom text query sent to Google Places}
        {--max-results= : Max places per destination (default from config)}
        {--language= : Language code, default from config}
        {--region= : Region code, default from config}
        {--island= : Island key (sumatra, java, bali, etc.)}
        {--categories=* : Category keys, can be repeated}
        {--dry-run : Fetch and map data without writing to database}';

    protected $description = 'Sync tourist attractions from Google Places API into local tourist_attractions table';

    public function handle(
        GooglePlacesService $googlePlacesService,
        TouristAttractionGoogleSyncService $syncService
    ): int {
        if (! $googlePlacesService->isConfigured()) {
            $this->error('GOOGLE_MAPS_PLACES_API_KEY is not configured.');

            return self::FAILURE;
        }

        $destinationId = (int) $this->option('destination_id');
        $allDestinations = (bool) $this->option('all-destinations');
        if ($destinationId <= 0 && ! $allDestinations) {
            $this->error('Provide --destination_id=<id> or --all-destinations.');

            return self::FAILURE;
        }

        $destinations = Destination::query()
            ->where('is_active', true)
            ->when($destinationId > 0, fn ($query) => $query->where('id', $destinationId))
            ->orderBy('id')
            ->get(['id', 'name', 'city', 'province', 'country', 'location', 'timezone']);

        if ($destinations->isEmpty()) {
            $this->warn('No active destination found for this sync run.');

            return self::SUCCESS;
        }

        $options = [
            'query' => $this->option('query'),
            'max_results' => $this->option('max-results'),
            'language_code' => $this->option('language'),
            'region_code' => $this->option('region'),
            'island_key' => $this->option('island'),
            'category_keys' => array_values(array_filter((array) $this->option('categories'))),
            'dry_run' => (bool) $this->option('dry-run'),
        ];

        $rows = [];
        $total = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'invalid' => 0,
        ];

        foreach ($destinations as $destination) {
            $this->info(sprintf('Syncing destination #%d %s ...', $destination->id, $destination->name));
            try {
                $result = $syncService->syncDestination($destination, $options);
                $rows[] = [
                    $result['destination_id'],
                    $result['destination_name'],
                    $result['fetched'],
                    $result['created'],
                    $result['updated'],
                    $result['skipped'],
                    $result['invalid'],
                ];

                $total['fetched'] += (int) $result['fetched'];
                $total['created'] += (int) $result['created'];
                $total['updated'] += (int) $result['updated'];
                $total['skipped'] += (int) $result['skipped'];
                $total['invalid'] += (int) $result['invalid'];

                if ((bool) $this->option('dry-run') && ! empty($result['items'])) {
                    $previewRows = array_slice($result['items'], 0, 10);
                    $this->line('Preview (first 10 rows):');
                    $this->table(
                        ['name', 'google_place_id', 'city', 'province', 'lat', 'lng'],
                        array_map(
                            fn (array $item): array => [
                                $item['name'] ?? '-',
                                $item['google_place_id'] ?? '-',
                                $item['city'] ?? '-',
                                $item['province'] ?? '-',
                                $item['latitude'] ?? '-',
                                $item['longitude'] ?? '-',
                            ],
                            $previewRows
                        )
                    );
                }
            } catch (\Throwable $exception) {
                $this->error(sprintf(
                    'Failed syncing destination #%d (%s): %s',
                    $destination->id,
                    $destination->name,
                    $exception->getMessage()
                ));
            }
        }

        $this->newLine();
        $this->table(
            ['ID', 'Destination', 'Fetched', 'Created', 'Updated', 'Skipped', 'Invalid'],
            $rows
        );

        $this->info(sprintf(
            'Completed. fetched=%d created=%d updated=%d skipped=%d invalid=%d',
            $total['fetched'],
            $total['created'],
            $total['updated'],
            $total['skipped'],
            $total['invalid']
        ));

        return self::SUCCESS;
    }
}
