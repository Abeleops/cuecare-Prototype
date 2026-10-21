<?php

namespace App\Observers;

use App\Models\BilliardTable;
use App\Services\AuditService;

/** Records billiard table rate changes and deletions in the audit trail. */
class BilliardTableObserver
{
    /** Audits changed hourly and half-hour rates for the updated table. */
    public function updated(BilliardTable $table): void
    {
        if ($table->wasChanged([
            'hourly_rate',
            'price_per_half_hour',
        ])) {
            app(AuditService::class)->record(
                'billiards.rate_changed',
                $table,
                [
                    'hourly_rate' => $table->getOriginal('hourly_rate'),
                    'price_per_half_hour' => $table->getOriginal('price_per_half_hour'),
                ],
                $table->only([
                    'hourly_rate',
                    'price_per_half_hour',
                ]),
            );
        }
    }

    /** Audits the deleted table's name and rates. */
    public function deleted(BilliardTable $table): void
    {
        app(AuditService::class)->record(
            'billiards.table_deleted',
            $table,
            null,
            [
                'name' => $table->name,
                'hourly_rate' => $table->hourly_rate,
                'price_per_half_hour' => $table->price_per_half_hour,
            ],
        );
    }
}
