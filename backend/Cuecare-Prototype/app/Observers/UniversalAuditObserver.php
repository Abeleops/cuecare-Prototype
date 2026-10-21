<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Records model lifecycle events while excluding account secrets. */
class UniversalAuditObserver
{
    /** Audits the created model's current values. */
    public function created(Model $model): void
    {
        app(AuditService::class)->record(
            $this->action($model, 'created'),
            $model,
            null,
            $this->snapshot($model),
        );
    }

    /** Audits the updated model's original and current values. */
    public function updated(Model $model): void
    {
        app(AuditService::class)->record(
            $this->action($model, 'updated'),
            $model,
            $this->snapshot($model, true),
            $this->snapshot($model),
        );
    }

    /** Audits the deleted model's original values. */
    public function deleted(Model $model): void
    {
        app(AuditService::class)->record(
            $this->action($model, 'deleted'),
            $model,
            $this->snapshot($model, true),
            null,
        );
    }

    private function action(Model $model, string $event): string
    {
        return Str::snake(class_basename($model)).'.'.$event;
    }

    private function snapshot(Model $model, bool $original = false): array
    {
        $values = $original ? $model->getRawOriginal() : $model->getAttributes();

        return collect($values)
            ->except([
                'password',
                'remember_token',
            ])
            ->all();
    }
}
