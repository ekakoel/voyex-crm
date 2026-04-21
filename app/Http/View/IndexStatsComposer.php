<?php

namespace App\Http\View;

use App\Support\SchemaInspector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IndexStatsComposer
{
    private array $modelMap = [
        'activities' => \App\Models\Activity::class,
        'airports' => \App\Models\Airport::class,
        'bookings' => \App\Models\Booking::class,
        'currencies' => \App\Models\Currency::class,
        'customers' => \App\Models\Customer::class,
        'destinations' => \App\Models\Destination::class,
        'food-beverages' => \App\Models\FoodBeverage::class,
        'inquiries' => \App\Models\Inquiry::class,
        'invoices' => \App\Models\Invoice::class,
        'itineraries' => \App\Models\Itinerary::class,
        'quotations' => \App\Models\Quotation::class,
        'roles' => \App\Models\Role::class,
        'services' => \App\Models\Module::class,
        'tourist-attractions' => \App\Models\TouristAttraction::class,
        'transports' => \App\Models\Transport::class,
        'users' => \App\Models\User::class,
        'vendors' => \App\Models\Vendor::class,
    ];

    public function compose(View $view): void
    {
        $data = $view->getData();
        if (! empty($data['statsCards'] ?? null)) {
            return;
        }

        $name = (string) $view->getName();
        $parts = explode('.', $name);
        $module = $parts[1] ?? null;

        $modelClass = $module && array_key_exists($module, $this->modelMap)
            ? $this->modelMap[$module]
            : null;

        if (! $modelClass || ! class_exists($modelClass)) {
            $view->with('statsCards', []);
            return;
        }

        $model = new $modelClass();
        $table = method_exists($model, 'getTable') ? $model->getTable() : null;
        $cacheKey = 'index_stats:' . $module . ':v1';

        $cards = Cache::remember($cacheKey, now()->addSeconds(120), function () use ($module, $table, $modelClass): array {
            $cards = [];

            if ($module === 'inquiries' && $table && SchemaInspector::hasColumn($table, 'priority')) {
                $priorityOptions = ['low', 'normal', 'high'];
                $counts = $modelClass::query()
                    ->select('priority', \DB::raw('COUNT(*) as total'))
                    ->groupBy('priority')
                    ->pluck('total', 'priority');

                foreach ($priorityOptions as $priority) {
                    $cards[] = [
                        'key' => 'priority_' . $priority,
                        'label' => Str::headline((string) $priority),
                        'value' => (int) ($counts[$priority] ?? 0),
                        'caption' => 'Priority',
                        'tone' => $this->toneForPriority((string) $priority),
                    ];
                }
            } elseif ($this->hasStatusOptions($modelClass)) {
                $statusOptions = $modelClass::STATUS_OPTIONS;
                $counts = $modelClass::query()
                    ->select('status', \DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                foreach ($statusOptions as $status) {
                    $cards[] = [
                        'key' => (string) $status,
                        'label' => Str::headline((string) $status),
                        'value' => (int) ($counts[$status] ?? 0),
                        'caption' => 'Total',
                        'tone' => $this->toneForStatus((string) $status),
                    ];
                }
            } elseif ($table && SchemaInspector::hasColumn($table, 'is_active')) {
                $counts = $modelClass::query()
                    ->select('is_active', \DB::raw('COUNT(*) as total'))
                    ->groupBy('is_active')
                    ->pluck('total', 'is_active');

                $cards[] = [
                    'key' => 'total',
                    'label' => 'Total',
                    'value' => (int) $counts->sum(),
                    'caption' => 'Total',
                    'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
                ];
                $cards[] = [
                    'key' => 'active',
                    'label' => 'Active',
                    'value' => (int) ($counts[1] ?? 0),
                    'caption' => 'Active',
                    'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                ];
                $cards[] = [
                    'key' => 'inactive',
                    'label' => 'Inactive',
                    'value' => (int) ($counts[0] ?? 0),
                    'caption' => 'Inactive',
                    'tone' => 'bg-slate-100 text-slate-700 border-slate-200',
                ];
            } elseif ($table && SchemaInspector::hasColumn($table, 'is_enabled')) {
                $counts = $modelClass::query()
                    ->select('is_enabled', \DB::raw('COUNT(*) as total'))
                    ->groupBy('is_enabled')
                    ->pluck('total', 'is_enabled');

                $cards[] = [
                    'key' => 'total',
                    'label' => 'Total',
                    'value' => (int) $counts->sum(),
                    'caption' => 'Total',
                    'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
                ];
                $cards[] = [
                    'key' => 'enabled',
                    'label' => 'Enabled',
                    'value' => (int) ($counts[1] ?? 0),
                    'caption' => 'Enabled',
                    'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                ];
                $cards[] = [
                    'key' => 'disabled',
                    'label' => 'Disabled',
                    'value' => (int) ($counts[0] ?? 0),
                    'caption' => 'Disabled',
                    'tone' => 'bg-slate-100 text-slate-700 border-slate-200',
                ];
            } else {
                $cards[] = [
                    'key' => 'total',
                    'label' => 'Total',
                    'value' => (int) $modelClass::query()->count(),
                    'caption' => 'Total',
                    'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
                ];
            }

            return $cards;
        });

        $view->with('statsCards', $cards);
    }

    private function hasStatusOptions(string $modelClass): bool
    {
        return defined($modelClass . '::STATUS_OPTIONS');
    }

    private function toneForStatus(string $status): string
    {
        return match (strtolower($status)) {
            'draft' => 'bg-rose-50 text-rose-700 border-rose-100',
            'processed' => 'bg-amber-50 text-amber-700 border-amber-100',
            'pending' => 'bg-sky-50 text-sky-700 border-sky-100',
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'rejected' => 'bg-slate-100 text-slate-700 border-slate-200',
            'final' => 'bg-violet-50 text-violet-700 border-violet-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }

    private function toneForPriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'low' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'normal' => 'bg-sky-50 text-sky-700 border-sky-100',
            'high' => 'bg-rose-50 text-rose-700 border-rose-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }
}
