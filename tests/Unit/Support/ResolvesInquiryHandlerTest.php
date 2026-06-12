<?php

namespace Tests\Unit\Support;

use App\Models\Inquiry;
use App\Support\Concerns\ResolvesInquiryHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResolvesInquiryHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inquiries');
        Schema::enableForeignKeyConstraints();

        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inquiries');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_apply_inquiry_handler_scope_prioritizes_handled_by_over_assigned_to_and_created_by(): void
    {
        DB::table('inquiries')->insert([
            [
                'id' => 1,
                'handled_by' => 99,
                'assigned_to' => 5,
                'created_by' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'handled_by' => null,
                'assigned_to' => 5,
                'created_by' => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'handled_by' => 0,
                'assigned_to' => 0,
                'created_by' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $helper = new class {
            use ResolvesInquiryHandler;

            public function matchingIds(int $userId): array
            {
                $query = Inquiry::query();
                $this->applyInquiryHandlerScope($query, $userId, 'inquiries');

                return $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
            }
        };

        $this->assertSame([2, 3], $helper->matchingIds(5));
    }

    public function test_resolve_inquiry_handler_id_uses_fallback_order(): void
    {
        DB::table('inquiries')->insert([
            'id' => 11,
            'handled_by' => 7,
            'assigned_to' => 5,
            'created_by' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inquiries')->insert([
            'id' => 12,
            'handled_by' => null,
            'assigned_to' => 5,
            'created_by' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inquiries')->insert([
            'id' => 13,
            'handled_by' => 0,
            'assigned_to' => 0,
            'created_by' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $handledInquiry = Inquiry::query()->findOrFail(11);
        $assignedInquiry = Inquiry::query()->findOrFail(12);
        $createdInquiry = Inquiry::query()->findOrFail(13);

        $helper = new class {
            use ResolvesInquiryHandler;

            public function handlerId(Inquiry $inquiry): int
            {
                return $this->resolveInquiryHandlerId($inquiry);
            }
        };

        $this->assertSame(7, $helper->handlerId($handledInquiry));
        $this->assertSame(5, $helper->handlerId($assignedInquiry));
        $this->assertSame(3, $helper->handlerId($createdInquiry));
    }
}
