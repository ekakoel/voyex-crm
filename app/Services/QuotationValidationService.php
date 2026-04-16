<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FoodBeverage;
use App\Models\Hotel;
use App\Models\HotelPrice;
use App\Models\HotelRoom;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemValidation;
use App\Models\ServiceRateHistory;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\TransportUnit;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationValidationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_VALID = 'valid';

    public function isValidationActor($user): bool
    {
        return (bool) ($user?->hasAnyRole(['Reservation', 'Manager', 'Director']));
    }

    public function prepareValidationPageData(Quotation $quotation): array
    {
        $this->syncValidationRequirementsAndMasterRates($quotation);

        $validationItems = $quotation->items()
            ->where('is_validation_required', true)
            ->with([
                'validator:id,name',
                'serviceable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Activity::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        FoodBeverage::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        Transport::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        TransportUnit::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        TouristAttraction::class => ['destination:id,name'],
                        HotelRoom::class => ['hotel:id,name,contact_person,phone,address,web', 'prices'],
                    ]);
                },
            ])
            ->orderBy('id')
            ->get();

        $quotation->load([
            'inquiry.customer',
            'items',
        ]);
        $progress = $this->getProgress($quotation);

        return [
            'quotation' => $quotation,
            'progress' => $progress,
            'validationItems' => $validationItems,
        ];
    }

    public function buildValidationItemDetail(Quotation $quotation, QuotationItem $item): array
    {
        if ((int) $item->quotation_id !== (int) $quotation->id) {
            throw ValidationException::withMessages([
                'item' => 'Invalid quotation item.',
            ]);
        }

        $item->refresh();
        $this->syncRateFromMasterForItem($item);
        $item->refresh();

        $item->loadMissing([
            'validator:id,name',
            'serviceable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Activity::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                    FoodBeverage::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                    Transport::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                    TransportUnit::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                    TouristAttraction::class => ['destination:id,name'],
                    HotelRoom::class => ['hotel:id,name,contact_person,phone,address,web', 'prices'],
                ]);
            },
        ]);

        $serviceable = $item->serviceable;
        $serviceableType = class_basename((string) ($item->serviceable_type ?? ''));
        $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];

        $vendorProviderName = '-';
        $contactName = '-';
        $contactPhone = '-';
        $contactEmail = '-';
        $contactWebsite = '-';
        $contactAddress = '-';

        if ($serviceable instanceof Activity || $serviceable instanceof FoodBeverage || $serviceable instanceof TransportUnit || $serviceable instanceof Transport) {
            $vendor = $serviceable->vendor;
            if (! $vendor && (int) ($serviceable->vendor_id ?? 0) > 0) {
                $vendor = Vendor::withTrashed()->find((int) $serviceable->vendor_id);
            }

            $vendorProviderName = $vendor?->name ?? ($serviceable->name ?? '-');
            $contactName = $this->firstNonEmptyString([
                $vendor?->contact_name,
                $serviceable->contact_name ?? null,
            ]) ?? '-';
            $contactPhone = $this->firstNonEmptyString([
                $vendor?->contact_phone,
                $serviceable->contact_phone ?? null,
            ]) ?? '-';
            $contactEmail = $this->firstNonEmptyString([
                $vendor?->contact_email,
                $serviceable->contact_email ?? null,
            ]) ?? '-';
            $contactWebsite = $this->firstNonEmptyString([
                $vendor?->website,
                $serviceable->website ?? null,
                $serviceable->web ?? null,
            ]) ?? '-';
            $contactAddress = $this->firstNonEmptyString([
                $vendor?->address,
                $vendor?->location,
                $serviceable->address ?? null,
                $serviceable->location ?? null,
            ]) ?? '-';
        } elseif ($serviceable instanceof HotelRoom) {
            $vendorProviderName = $serviceable->hotel?->name ?? $serviceable->rooms ?? '-';
            $contactName = $this->firstNonEmptyString([
                $serviceable->hotel?->contact_person,
            ]) ?? '-';
            $contactPhone = $this->firstNonEmptyString([
                $serviceable->hotel?->phone,
            ]) ?? '-';
            $contactEmail = '-';
            $contactWebsite = $this->firstNonEmptyString([
                $serviceable->hotel?->web,
                $serviceable->hotel?->website ?? null,
            ]) ?? '-';
            $contactAddress = $this->firstNonEmptyString([
                $serviceable->hotel?->address,
                $serviceable->hotel?->location ?? null,
            ]) ?? '-';
        } elseif ($serviceable instanceof TouristAttraction) {
            $vendorProviderName = $serviceable->name ?? '-';
            $contactWebsite = $this->firstNonEmptyString([
                $serviceable->google_maps_url,
                $serviceable->website ?? null,
                $serviceable->web ?? null,
            ]) ?? '-';
            $contactAddress = $this->firstNonEmptyString([
                $serviceable->address,
                $serviceable->location,
            ]) ?? '-';
        }

        $contactOverride = is_array(Arr::get($meta, 'validation_contact')) ? Arr::get($meta, 'validation_contact') : [];
        $contactName = $this->resolveContactFieldFromOverride($contactName, $contactOverride, 'contact_name');
        $contactPhone = $this->resolveContactFieldFromOverride($contactPhone, $contactOverride, 'contact_phone');
        $contactEmail = $this->resolveContactFieldFromOverride($contactEmail, $contactOverride, 'contact_email');
        $contactWebsite = $this->resolveContactFieldFromOverride($contactWebsite, $contactOverride, 'contact_website');
        $contactAddress = $this->resolveContactFieldFromOverride($contactAddress, $contactOverride, 'contact_address');

        return [
            'item' => [
                'id' => (int) $item->id,
                'description' => (string) ($item->description ?? ''),
                'serviceable_type' => $serviceableType,
                'serviceable_meta' => $meta,
                'contract_rate' => (float) ($item->contract_rate ?? 0),
                'markup_type' => $this->normalizeMarkupType((string) ($item->markup_type ?? 'fixed')),
                'markup' => (float) ($item->markup ?? 0),
                'updated_at' => optional($item->updated_at)->toIso8601String(),
                'validator' => $item->validator?->name,
            ],
            'contact' => [
                'vendor_provider_name' => $vendorProviderName,
                'contact_name' => $contactName,
                'contact_phone' => $contactPhone,
                'contact_email' => $contactEmail,
                'contact_website' => $contactWebsite,
                'contact_address' => $contactAddress,
            ],
            // Keep response compact for large quotations: modal only needs the values currently used by this quotation item.
            'history' => [],
        ];
    }

    public function updateItemContact(Quotation $quotation, QuotationItem $item, array $payload, int $actorId): array
    {
        if ((int) $item->quotation_id !== (int) $quotation->id) {
            throw ValidationException::withMessages([
                'item' => 'Invalid quotation item.',
            ]);
        }

        return DB::transaction(function () use ($quotation, $item, $payload, $actorId): array {
            $item->loadMissing([
                'serviceable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Activity::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        FoodBeverage::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        Transport::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        TransportUnit::class => ['vendor:id,name,contact_name,contact_email,contact_phone,website,address,location'],
                        HotelRoom::class => ['hotel:id,name,contact_person,phone,address,web'],
                        TouristAttraction::class => [],
                    ]);
                },
            ]);

            $oldDetail = $this->buildValidationItemDetail($quotation, $item);
            $oldContact = (array) ($oldDetail['contact'] ?? []);

            $newContact = [
                'contact_name' => $this->normalizeNullableText($payload['contact_name'] ?? null),
                'contact_phone' => $this->normalizeNullableText($payload['contact_phone'] ?? null),
                'contact_email' => $this->normalizeNullableText($payload['contact_email'] ?? null),
                'contact_website' => $this->normalizeNullableText($payload['contact_website'] ?? null),
                'contact_address' => $this->normalizeNullableText($payload['contact_address'] ?? null),
            ];

            $serviceable = $item->serviceable;
            $sourceType = null;
            $sourceId = null;
            $sourcePatch = [];

            if ($serviceable instanceof Activity || $serviceable instanceof FoodBeverage || $serviceable instanceof TransportUnit || $serviceable instanceof Transport) {
                $vendor = $serviceable->vendor;
                if (! $vendor && (int) ($serviceable->vendor_id ?? 0) > 0) {
                    $vendor = Vendor::withTrashed()->find((int) $serviceable->vendor_id);
                }
                if ($vendor instanceof Vendor) {
                    $vendor->fill([
                        'contact_name' => $newContact['contact_name'],
                        'contact_phone' => $newContact['contact_phone'],
                        'contact_email' => $newContact['contact_email'],
                        'website' => $newContact['contact_website'],
                        'address' => $newContact['contact_address'],
                    ])->save();

                    $sourceType = Vendor::class;
                    $sourceId = (int) $vendor->id;
                    $sourcePatch = [
                        'contact_name' => $vendor->contact_name,
                        'contact_phone' => $vendor->contact_phone,
                        'contact_email' => $vendor->contact_email,
                        'contact_website' => $vendor->website,
                        'contact_address' => $vendor->address,
                    ];
                }
            } elseif ($serviceable instanceof HotelRoom) {
                $hotel = $serviceable->hotel;
                if ($hotel instanceof Hotel) {
                    $hotel->fill([
                        'contact_person' => $newContact['contact_name'],
                        'phone' => $newContact['contact_phone'],
                        'web' => $newContact['contact_website'],
                        'address' => $newContact['contact_address'],
                    ])->save();

                    $sourceType = Hotel::class;
                    $sourceId = (int) $hotel->id;
                    $sourcePatch = [
                        'contact_name' => $hotel->contact_person,
                        'contact_phone' => $hotel->phone,
                        'contact_email' => $newContact['contact_email'],
                        'contact_website' => $hotel->web,
                        'contact_address' => $hotel->address,
                    ];
                }
            } elseif ($serviceable instanceof TouristAttraction) {
                $serviceable->fill([
                    'address' => $newContact['contact_address'],
                ])->save();

                $sourceType = TouristAttraction::class;
                $sourceId = (int) $serviceable->id;
                $sourcePatch = [
                    'contact_name' => $newContact['contact_name'],
                    'contact_phone' => $newContact['contact_phone'],
                    'contact_email' => $newContact['contact_email'],
                    'contact_website' => $newContact['contact_website'],
                    'contact_address' => $serviceable->address,
                ];
            }

            $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
            $meta['validation_contact'] = [
                'contact_name' => $newContact['contact_name'],
                'contact_phone' => $newContact['contact_phone'],
                'contact_email' => $newContact['contact_email'],
                'contact_website' => $newContact['contact_website'],
                'contact_address' => $newContact['contact_address'],
            ];
            $item->serviceable_meta = $meta;
            $item->save();

            QuotationItemValidation::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'validator_id' => $actorId,
                'action' => 'update_contact',
                'is_validated' => (bool) ($item->is_validated ?? false),
                'validation_notes' => 'Contact details updated from validation modal.',
                'source_rate_type' => $sourceType,
                'source_rate_id' => $sourceId,
                'source_rate_snapshot' => [
                    'contact_before' => $oldContact,
                    'contact_after' => $sourcePatch !== [] ? $sourcePatch : $newContact,
                ],
            ]);

            return $this->buildValidationItemDetail($quotation, $item->fresh());
        });
    }

    public function saveProgress(Quotation $quotation, array $itemsPayload, int $actorId): array
    {
        return DB::transaction(function () use ($quotation, $itemsPayload, $actorId): array {
            $this->syncValidationRequirements($quotation);
            $quotation->loadMissing('items');

            foreach ($itemsPayload as $itemId => $payload) {
                $item = $quotation->items->firstWhere('id', (int) $itemId);
                if (! $item || ! (bool) ($item->is_validation_required ?? false)) {
                    continue;
                }

                $normalizedPayload = (array) $payload;
                $willValidate = array_key_exists('is_validated', $normalizedPayload)
                    ? filter_var($normalizedPayload['is_validated'], FILTER_VALIDATE_BOOL)
                    : false;
                if (! $willValidate) {
                    unset($normalizedPayload['contract_rate'], $normalizedPayload['markup_type'], $normalizedPayload['markup']);
                }

                $this->applyItemUpdate($quotation, $item, $normalizedPayload, $actorId, 'save_progress', false);
            }

            return $this->refreshProgress($quotation, $actorId);
        });
    }

    public function saveItem(Quotation $quotation, QuotationItem $item, array $payload, int $actorId): array
    {
        if ((int) $item->quotation_id !== (int) $quotation->id) {
            throw ValidationException::withMessages([
                'item' => 'Invalid quotation item.',
            ]);
        }

        return DB::transaction(function () use ($quotation, $item, $payload, $actorId): array {
            $this->syncValidationRequirements($quotation);
            $item->refresh();

            if (! (bool) ($item->is_validation_required ?? false)) {
                throw ValidationException::withMessages([
                    'item' => 'This item does not require validation.',
                ]);
            }

            $this->applyItemUpdate($quotation, $item, $payload, $actorId, 'save_item', false);

            return $this->refreshProgress($quotation, $actorId);
        });
    }

    public function validateSelected(Quotation $quotation, array $itemIds, int $actorId): array
    {
        return DB::transaction(function () use ($quotation, $itemIds, $actorId): array {
            $this->syncValidationRequirements($quotation);
            $quotation->loadMissing('items');

            foreach ($itemIds as $itemId) {
                $item = $quotation->items->firstWhere('id', (int) $itemId);
                if (! $item || ! (bool) ($item->is_validation_required ?? false)) {
                    continue;
                }

                $this->applyItemUpdate($quotation, $item, ['is_validated' => true], $actorId, 'validate_selected', true);
            }

            return $this->refreshProgress($quotation, $actorId);
        });
    }

    public function finalize(Quotation $quotation, int $actorId): array
    {
        return DB::transaction(function () use ($quotation, $actorId): array {
            $this->syncValidationRequirements($quotation);
            $progress = $this->refreshProgress($quotation, $actorId);

            if ((int) $progress['total_required'] <= 0) {
                throw ValidationException::withMessages([
                    'quotation' => 'No validation-required items found in this quotation.',
                ]);
            }
            if (! (bool) $progress['is_complete']) {
                throw ValidationException::withMessages([
                    'quotation' => 'Quotation validation is not complete yet.',
                ]);
            }

            if ((string) ($quotation->validation_status ?? self::STATUS_PENDING) !== self::STATUS_VALID
                || ! $quotation->validated_at
                || (int) ($quotation->validated_by ?? 0) !== $actorId) {
                $quotation->update([
                    'validation_status' => self::STATUS_VALID,
                    'validated_at' => now(),
                    'validated_by' => $actorId,
                ]);
            }

            $this->logQuotationFinalValidation($quotation, $actorId);

            return $this->getProgress($quotation->fresh('items'));
        });
    }

    public function syncValidationRequirements(Quotation $quotation): void
    {
        $quotation->loadMissing('items');

        foreach ($quotation->items as $item) {
            $required = $this->isValidationRequired($item);

            $patch = [];
            if ((bool) ($item->is_validation_required ?? false) !== $required) {
                $patch['is_validation_required'] = $required;
            }

            if (! $required && (bool) ($item->is_validated ?? false)) {
                $patch['is_validated'] = false;
                $patch['validated_at'] = null;
                $patch['validated_by'] = null;
            }

            if ($patch !== []) {
                $item->fill($patch);
                $item->save();
            }
        }

        $this->refreshProgress($quotation);
    }

    public function syncValidationRequirementsAndMasterRates(Quotation $quotation): void
    {
        $this->syncValidationRequirements($quotation);
        $this->syncRatesFromMaster($quotation);
    }

    public function getProgress(Quotation $quotation): array
    {
        $quotation->loadMissing('items');

        $totalItems = (int) $quotation->items->count();
        $totalRequired = (int) $quotation->items->where('is_validation_required', true)->count();
        $totalValidated = (int) $quotation->items
            ->where('is_validation_required', true)
            ->where('is_validated', true)
            ->count();

        $percent = $totalRequired > 0
            ? (int) round(($totalValidated / $totalRequired) * 100)
            : 0;

        $isComplete = $totalRequired > 0 && $totalValidated >= $totalRequired;

        return [
            'total_items' => $totalItems,
            'total_required' => $totalRequired,
            'total_validated' => $totalValidated,
            'validation_percent' => max(0, min(100, $percent)),
            'is_complete' => $isComplete,
            'requires_validation' => $totalRequired > 0,
            'status' => (string) ($quotation->validation_status ?? self::STATUS_PENDING),
        ];
    }

    public function requiresValidationBeforeApproval(Quotation $quotation): bool
    {
        $quotation->loadMissing('items');
        return $quotation->items->contains(fn (QuotationItem $item): bool => (bool) ($item->is_validation_required ?? false));
    }

    public function canBeApproved(Quotation $quotation): bool
    {
        if (! $this->requiresValidationBeforeApproval($quotation)) {
            return true;
        }

        return (string) ($quotation->validation_status ?? self::STATUS_PENDING) === self::STATUS_VALID;
    }

    private function applyItemUpdate(
        Quotation $quotation,
        QuotationItem $item,
        array $payload,
        int $actorId,
        string $action,
        bool $forceValidate
    ): void {
        $oldContractRate = (float) ($item->contract_rate ?? 0);
        $oldMarkupType = $this->normalizeMarkupType($item->markup_type ?? 'fixed');
        $oldMarkup = (float) ($item->markup ?? 0);
        $oldIsValidated = (bool) ($item->is_validated ?? false);

        $newContractRate = array_key_exists('contract_rate', $payload)
            ? (float) $payload['contract_rate']
            : $oldContractRate;
        $newMarkupType = array_key_exists('markup_type', $payload)
            ? $this->normalizeMarkupType($payload['markup_type'])
            : $oldMarkupType;
        $newMarkup = array_key_exists('markup', $payload)
            ? (float) $payload['markup']
            : $oldMarkup;

        if ($newMarkupType === 'percent' && $newMarkup > 100) {
            throw ValidationException::withMessages([
                'markup' => 'Markup percent cannot be greater than 100.',
            ]);
        }

        $newUnitPrice = $this->computePublishRate($newContractRate, $newMarkupType, $newMarkup);
        $newDiscountType = $this->normalizeMarkupType($item->discount_type ?? 'fixed');
        $newDiscount = (float) ($item->discount ?? 0);
        $newTotal = $this->computeItemTotal((int) ($item->qty ?? 1), $newUnitPrice, $newDiscountType, $newDiscount);

        $hasRateChange = ($newContractRate !== $oldContractRate)
            || ($newMarkupType !== $oldMarkupType)
            || ($newMarkup !== $oldMarkup);

        $newIsValidated = $oldIsValidated;
        if ($forceValidate) {
            $newIsValidated = true;
        } elseif (array_key_exists('is_validated', $payload)) {
            $newIsValidated = filter_var($payload['is_validated'], FILTER_VALIDATE_BOOL);
        }
        if ($hasRateChange && ! $forceValidate && ! array_key_exists('is_validated', $payload)) {
            $newIsValidated = false;
        }

        if ($newIsValidated) {
            $this->assertItemCanBeValidated($newContractRate, $newMarkupType, $newMarkup);
        }

        $patch = [];
        if ($hasRateChange) {
            $patch['contract_rate'] = $newContractRate;
            $patch['markup_type'] = $newMarkupType;
            $patch['markup'] = $newMarkup;
            $patch['unit_price'] = $newUnitPrice;
            $patch['total'] = $newTotal;
        }

        if (array_key_exists('validation_notes', $payload)) {
            $patch['validation_notes'] = trim((string) ($payload['validation_notes'] ?? ''));
        }

        if ($newIsValidated) {
            $patch['is_validated'] = true;
            $patch['validated_at'] = now();
            $patch['validated_by'] = $actorId;
            $patch['last_validated_contract_rate'] = $newContractRate;
            $patch['last_validated_markup_type'] = $newMarkupType;
            $patch['last_validated_markup'] = $newMarkup;
        } else {
            $patch['is_validated'] = false;
            $patch['validated_at'] = null;
            $patch['validated_by'] = null;
        }

        if ($patch !== []) {
            $item->fill($patch);
            $item->save();
        }

        $sourceUpdate = null;
        if ($hasRateChange) {
            $sourceUpdate = $this->updateMasterRateFromItem($quotation, $item->fresh(), $actorId, $patch['validation_notes'] ?? null);
        }

        $hasValidationChange = $oldIsValidated !== (bool) ($item->is_validated ?? false);
        $hasNotesChange = array_key_exists('validation_notes', $patch);

        if ($hasRateChange || $hasValidationChange || $hasNotesChange) {
            QuotationItemValidation::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'validator_id' => $actorId,
                'action' => $action,
                'is_validated' => (bool) ($item->is_validated ?? false),
                'validation_notes' => $item->validation_notes,
                'old_contract_rate' => $oldContractRate,
                'new_contract_rate' => (float) ($item->contract_rate ?? 0),
                'old_markup_type' => $oldMarkupType,
                'new_markup_type' => $this->normalizeMarkupType($item->markup_type ?? 'fixed'),
                'old_markup' => $oldMarkup,
                'new_markup' => (float) ($item->markup ?? 0),
                'source_rate_type' => $sourceUpdate['type'] ?? null,
                'source_rate_id' => $sourceUpdate['id'] ?? null,
                'source_rate_snapshot' => $sourceUpdate['snapshot'] ?? null,
            ]);
        }
    }

    private function updateMasterRateFromItem(Quotation $quotation, QuotationItem $item, int $actorId, ?string $notes = null): ?array
    {
        $serviceableId = (int) ($item->serviceable_id ?? 0);
        if ($serviceableId <= 0) {
            return null;
        }

        $contractRate = (float) ($item->contract_rate ?? 0);
        $markupType = $this->normalizeMarkupType($item->markup_type ?? 'fixed');
        $markup = (float) ($item->markup ?? 0);
        $publishRate = $this->computePublishRate($contractRate, $markupType, $markup);

        $today = now()->startOfDay();
        $nextMonth = now()->addMonth()->startOfDay();

        $serviceType = (string) ($item->serviceable_type ?? '');
        if ($this->isServiceableType($serviceType, HotelRoom::class)) {
            $room = HotelRoom::query()->with(['hotel', 'prices'])->find($serviceableId);
            if (! $room) {
                return null;
            }

            $lastPrice = $room->prices
                ->sortByDesc(fn ($price) => $price->start_date ?? $price->id)
                ->first();

            $hotelPrice = HotelPrice::query()->create([
                'hotels_id' => (int) $room->hotels_id,
                'rooms_id' => (int) $room->id,
                'start_date' => $today->toDateString(),
                'end_date' => $nextMonth->toDateString(),
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
                'kick_back' => $lastPrice?->kick_back,
                'author' => $actorId,
            ]);

            ServiceRateHistory::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'serviceable_type' => HotelRoom::class,
                'serviceable_id' => $serviceableId,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
                'start_date' => $today->toDateString(),
                'end_date' => $nextMonth->toDateString(),
                'notes' => $notes,
                'updated_by' => $actorId,
            ]);

            return [
                'type' => HotelPrice::class,
                'id' => (int) $hotelPrice->id,
                'snapshot' => [
                    'contract_rate' => $contractRate,
                    'markup_type' => $markupType,
                    'markup' => $markup,
                    'publish_rate' => $publishRate,
                    'start_date' => $today->toDateString(),
                    'end_date' => $nextMonth->toDateString(),
                ],
            ];
        }

        if ($this->isServiceableType($serviceType, Activity::class)) {
            $activity = Activity::query()->find($serviceableId);
            if (! $activity) {
                return null;
            }

            $paxType = strtolower((string) Arr::get($item->serviceable_meta ?? [], 'pax_type', 'adult'));
            if ($paxType === 'child') {
                $activity->update([
                    'child_contract_rate' => $contractRate,
                    'child_markup_type' => $markupType,
                    'child_markup' => $markup,
                    'child_publish_rate' => $publishRate,
                ]);
            } else {
                $activity->update([
                    'adult_contract_rate' => $contractRate,
                    'adult_markup_type' => $markupType,
                    'adult_markup' => $markup,
                    'adult_publish_rate' => $publishRate,
                ]);
            }

            ServiceRateHistory::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'serviceable_type' => Activity::class,
                'serviceable_id' => $serviceableId,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
                'start_date' => $today->toDateString(),
                'end_date' => $nextMonth->toDateString(),
                'notes' => $notes,
                'updated_by' => $actorId,
            ]);

            return [
                'type' => Activity::class,
                'id' => $activity->id,
                'snapshot' => ['pax_type' => $paxType, 'publish_rate' => $publishRate],
            ];
        }

        if ($this->isServiceableType($serviceType, FoodBeverage::class)) {
            $food = FoodBeverage::query()->find($serviceableId);
            if (! $food) {
                return null;
            }

            $food->update([
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
            ]);

            ServiceRateHistory::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'serviceable_type' => FoodBeverage::class,
                'serviceable_id' => $serviceableId,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
                'start_date' => $today->toDateString(),
                'end_date' => $nextMonth->toDateString(),
                'notes' => $notes,
                'updated_by' => $actorId,
            ]);

            return [
                'type' => FoodBeverage::class,
                'id' => $food->id,
                'snapshot' => ['publish_rate' => $publishRate],
            ];
        }

        if ($this->isServiceableType($serviceType, TransportUnit::class)) {
            $transport = Transport::query()->find($serviceableId);
            if (! $transport) {
                return null;
            }

            $transport->update([
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => round($publishRate, 0),
            ]);

            ServiceRateHistory::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'serviceable_type' => TransportUnit::class,
                'serviceable_id' => $serviceableId,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
                'start_date' => $today->toDateString(),
                'end_date' => $nextMonth->toDateString(),
                'notes' => $notes,
                'updated_by' => $actorId,
            ]);

            return [
                'type' => Transport::class,
                'id' => $transport->id,
                'snapshot' => ['publish_rate' => $publishRate],
            ];
        }

        if ($this->isServiceableType($serviceType, TouristAttraction::class)) {
            $attraction = TouristAttraction::query()->find($serviceableId);
            if (! $attraction) {
                return null;
            }

            $attraction->update([
                'contract_rate_per_pax' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate_per_pax' => $publishRate,
            ]);

            ServiceRateHistory::query()->create([
                'quotation_id' => $quotation->id,
                'quotation_item_id' => $item->id,
                'serviceable_type' => TouristAttraction::class,
                'serviceable_id' => $serviceableId,
                'contract_rate' => $contractRate,
                'markup_type' => $markupType,
                'markup' => $markup,
                'publish_rate' => $publishRate,
                'start_date' => $today->toDateString(),
                'end_date' => $nextMonth->toDateString(),
                'notes' => $notes,
                'updated_by' => $actorId,
            ]);

            return [
                'type' => TouristAttraction::class,
                'id' => $attraction->id,
                'snapshot' => ['publish_rate' => $publishRate],
            ];
        }

        return null;
    }

    private function assertItemCanBeValidated(float $contractRate, string $markupType, float $markup): void
    {
        if ($contractRate < 0) {
            throw ValidationException::withMessages([
                'contract_rate' => 'Contract rate must be zero or greater.',
            ]);
        }
        if (! in_array($markupType, ['fixed', 'percent'], true)) {
            throw ValidationException::withMessages([
                'markup_type' => 'Markup type is invalid.',
            ]);
        }
        if ($markup < 0) {
            throw ValidationException::withMessages([
                'markup' => 'Markup must be zero or greater.',
            ]);
        }
        if ($markupType === 'percent' && $markup > 100) {
            throw ValidationException::withMessages([
                'markup' => 'Markup percent cannot be greater than 100.',
            ]);
        }
    }

    private function refreshProgress(Quotation $quotation, ?int $actorId = null): array
    {
        $quotation->loadMissing('items');
        $progress = $this->getProgress($quotation);

        $newStatus = self::STATUS_PENDING;
        if ((int) $progress['total_required'] > 0) {
            $newStatus = (bool) $progress['is_complete']
                ? self::STATUS_VALID
                : ((int) $progress['total_validated'] > 0 ? self::STATUS_PARTIAL : self::STATUS_PENDING);
        }

        $patch = [];
        if ((string) ($quotation->validation_status ?? self::STATUS_PENDING) !== $newStatus) {
            $patch['validation_status'] = $newStatus;
        }

        if ($newStatus === self::STATUS_VALID) {
            if ($actorId && (! $quotation->validated_at || (int) ($quotation->validated_by ?? 0) !== $actorId)) {
                $patch['validated_at'] = now();
                $patch['validated_by'] = $actorId;
            }
        } else {
            if ($quotation->validated_at || $quotation->validated_by) {
                $patch['validated_at'] = null;
                $patch['validated_by'] = null;
            }
        }

        if ($patch !== []) {
            $quotation->update($patch);
            $quotation->refresh();
            $progress = $this->getProgress($quotation);
        }

        return $progress;
    }

    private function isValidationRequired(QuotationItem $item): bool
    {
        $type = (string) ($item->serviceable_type ?? '');

        if (
            $this->isServiceableType($type, Activity::class)
            || $this->isServiceableType($type, FoodBeverage::class)
            || $this->isServiceableType($type, TransportUnit::class)
            || $this->isServiceableType($type, TouristAttraction::class)
        ) {
            return true;
        }

        if ($this->isServiceableType($type, HotelRoom::class)) {
            return $this->isHotelArrangedByUs($item);
        }

        return false;
    }

    private function isHotelArrangedByUs(QuotationItem $item): bool
    {
        $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];

        $bookingMode = strtolower((string) ($meta['end_hotel_booking_mode'] ?? 'arranged'));
        if ($bookingMode === 'self') {
            return false;
        }

        $condition = strtolower(trim((string) ($meta['item_condition'] ?? $meta['condition'] ?? '')));
        if ($condition !== '') {
            if (str_contains($condition, 'self')) {
                return false;
            }
            if (str_contains($condition, 'arranged by us')) {
                return true;
            }
        }

        return true;
    }

    private function normalizeMarkupType(?string $type): string
    {
        return strtolower((string) $type) === 'percent' ? 'percent' : 'fixed';
    }

    private function syncRatesFromMaster(Quotation $quotation): void
    {
        $quotation->loadMissing([
            'items.serviceable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Activity::class => [],
                    FoodBeverage::class => [],
                    TransportUnit::class => [],
                    TouristAttraction::class => [],
                    HotelRoom::class => ['prices'],
                ]);
            },
        ]);

        foreach ($quotation->items as $item) {
            if (! (bool) ($item->is_validation_required ?? false)) {
                continue;
            }

            $this->syncRateFromMasterForItem($item);
        }
    }

    private function syncRateFromMasterForItem(QuotationItem $item): bool
    {
        $serviceable = $item->serviceable;
        if (! $serviceable) {
            return false;
        }

        $resolved = $this->resolveMasterRateForItem($item);
        if (! $resolved) {
            return false;
        }

        $masterContractRate = (float) ($resolved['contract_rate'] ?? 0);
        $masterMarkupType = $this->normalizeMarkupType((string) ($resolved['markup_type'] ?? 'fixed'));
        $masterMarkup = (float) ($resolved['markup'] ?? 0);

        $currentContractRate = (float) ($item->contract_rate ?? 0);
        $currentMarkupType = $this->normalizeMarkupType((string) ($item->markup_type ?? 'fixed'));
        $currentMarkup = (float) ($item->markup ?? 0);

        $hasRateChange = ! $this->ratesAreEqual($currentContractRate, $masterContractRate)
            || $currentMarkupType !== $masterMarkupType
            || ! $this->ratesAreEqual($currentMarkup, $masterMarkup);

        if (! $hasRateChange) {
            return false;
        }

        $unitPrice = $this->computePublishRate($masterContractRate, $masterMarkupType, $masterMarkup);
        $discountType = $this->normalizeMarkupType((string) ($item->discount_type ?? 'fixed'));
        $discount = (float) ($item->discount ?? 0);
        $total = $this->computeItemTotal((int) ($item->qty ?? 1), $unitPrice, $discountType, $discount);

        $item->fill([
            'contract_rate' => $masterContractRate,
            'markup_type' => $masterMarkupType,
            'markup' => $masterMarkup,
            'unit_price' => $unitPrice,
            'total' => $total,
            'is_validated' => false,
            'validated_at' => null,
            'validated_by' => null,
        ]);
        $item->save();

        return true;
    }

    private function resolveMasterRateForItem(QuotationItem $item): ?array
    {
        $serviceable = $item->serviceable;
        $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];

        if ($serviceable instanceof Activity) {
            $paxType = strtolower((string) Arr::get($meta, 'pax_type', 'adult'));
            if ($paxType === 'child') {
                return [
                    'contract_rate' => (float) ($serviceable->child_contract_rate ?? 0),
                    'markup_type' => (string) ($serviceable->child_markup_type ?? 'fixed'),
                    'markup' => (float) ($serviceable->child_markup ?? 0),
                ];
            }

            return [
                'contract_rate' => (float) ($serviceable->adult_contract_rate ?? $serviceable->contract_price ?? 0),
                'markup_type' => (string) ($serviceable->adult_markup_type ?? 'fixed'),
                'markup' => (float) ($serviceable->adult_markup ?? 0),
            ];
        }

        if ($serviceable instanceof FoodBeverage) {
            return [
                'contract_rate' => (float) ($serviceable->contract_rate ?? 0),
                'markup_type' => (string) ($serviceable->markup_type ?? 'fixed'),
                'markup' => (float) ($serviceable->markup ?? 0),
            ];
        }

        if ($serviceable instanceof TransportUnit) {
            return [
                'contract_rate' => (float) ($serviceable->contract_rate ?? 0),
                'markup_type' => (string) ($serviceable->markup_type ?? 'fixed'),
                'markup' => (float) ($serviceable->markup ?? 0),
            ];
        }

        if ($serviceable instanceof TouristAttraction) {
            return [
                'contract_rate' => (float) ($serviceable->contract_rate_per_pax ?? 0),
                'markup_type' => (string) ($serviceable->markup_type ?? 'fixed'),
                'markup' => (float) ($serviceable->markup ?? 0),
            ];
        }

        if ($serviceable instanceof HotelRoom) {
            $today = now()->toDateString();
            $prices = $serviceable->prices
                ->sortByDesc(function ($price) {
                    return $price->start_date ?? $price->id;
                })
                ->values();

            $activePrice = $prices->first(function ($price) use ($today): bool {
                $start = $price->start_date ? (string) $price->start_date : null;
                $end = $price->end_date ? (string) $price->end_date : null;

                if ($start && $today < $start) {
                    return false;
                }
                if ($end && $today > $end) {
                    return false;
                }

                return true;
            }) ?? $prices->first();

            if (! $activePrice) {
                return null;
            }

            return [
                'contract_rate' => (float) ($activePrice->contract_rate ?? 0),
                'markup_type' => (string) ($activePrice->markup_type ?? 'fixed'),
                'markup' => (float) ($activePrice->markup ?? 0),
            ];
        }

        return null;
    }

    private function ratesAreEqual(float $left, float $right): bool
    {
        return abs($left - $right) < 0.0001;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function resolveContactFieldFromOverride(string $currentValue, array $override, string $key): string
    {
        if (! array_key_exists($key, $override)) {
            return $currentValue;
        }

        $overrideValue = $override[$key];
        if ($overrideValue === null) {
            return $currentValue;
        }

        $normalized = trim((string) $overrideValue);
        if ($normalized === '') {
            return $currentValue;
        }
        if ($normalized === '-') {
            return $currentValue;
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $candidates
     */
    private function firstNonEmptyString(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function isServiceableType(string $actualType, string $targetClass): bool
    {
        $actualType = trim($actualType);
        if ($actualType === '') {
            return false;
        }

        if ($actualType === $targetClass) {
            return true;
        }

        return class_basename($actualType) === class_basename($targetClass);
    }

    private function computePublishRate(float $contractRate, string $markupType, float $markup): float
    {
        if ($markupType === 'percent') {
            return max(0, $contractRate + ($contractRate * ($markup / 100)));
        }

        return max(0, $contractRate + $markup);
    }

    private function computeItemTotal(int $qty, float $unitPrice, string $discountType, float $discount): float
    {
        $qty = max(1, $qty);
        $subTotal = max(0, $qty * $unitPrice);

        $discountAmount = 0.0;
        if ($discountType === 'percent') {
            $discountAmount = $subTotal * (max(0, min(100, $discount)) / 100);
        } else {
            $discountAmount = max(0, $discount);
        }

        return max(0, $subTotal - $discountAmount);
    }

    private function logQuotationFinalValidation(Quotation $quotation, int $actorId): void
    {
        QuotationItemValidation::query()->create([
            'quotation_id' => $quotation->id,
            'quotation_item_id' => null,
            'validator_id' => $actorId,
            'action' => 'final_validate',
            'is_validated' => true,
            'validation_notes' => 'Quotation final validation completed.',
        ]);
    }
}
