<?php

namespace App\Providers;

use App\Models\ApprovalRequest;
use App\Models\BilliardSession;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\FoodOrder;
use App\Models\FoodOrderItem;
use App\Models\LoyaltyTransaction;
use App\Models\Payment;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SecurityAlert;
use App\Models\SecurityEvent;
use App\Models\SecurityRule;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\ServiceStatusHistory;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\Vehicle;
use App\Observers\UniversalAuditObserver;
use Illuminate\Support\ServiceProvider;

/** Registers application services and the shared model audit observer. */
class AppServiceProvider extends ServiceProvider
{
    /** Registers application service bindings. */
    public function register(): void
    {
        // No additional service bindings are registered.
    }

    /** Attaches the shared audit observer to the existing operational models. */
    public function boot(): void
    {
        // AuditLog is intentionally excluded to prevent recursive audit records.
        foreach ([
            ApprovalRequest::class,
            BilliardSession::class,
            CashShift::class,
            Customer::class,
            FoodOrder::class,
            FoodOrderItem::class,
            LoyaltyTransaction::class,
            Payment::class,
            Recipe::class,
            Sale::class,
            SaleItem::class,
            SecurityAlert::class,
            SecurityEvent::class,
            SecurityRule::class,
            ServiceBooking::class,
            ServiceCatalog::class,
            ServiceStatusHistory::class,
            StockMovement::class,
            Transaction::class,
            Vehicle::class,
        ] as $model) {
            $model::observe(UniversalAuditObserver::class);
        }
    }
}
