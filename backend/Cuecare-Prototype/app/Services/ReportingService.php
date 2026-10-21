<?php

namespace App\Services;

use App\Models\BilliardSession;
use App\Models\CashShift;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\SecurityAlert;
use App\Models\ServiceBooking;
use App\Models\Transaction;

/** Queries the shared ledger and operational records for dashboards and reports. */
class ReportingService
{
    /** Returns signed cash payment totals recorded against the supplied shift. */
    public function cashSalesForShift(CashShift $shift): float
    {
        return (float) Payment::where('method', 'cash')
            ->whereHas('transaction', fn ($query) => $query->where('shift_id', $shift->id))
            ->sum('amount');
    }

    /** Returns today's sales and current operational counts for the dashboard. */
    public function dashboardMetrics(): array
    {
        return [
            'sales' => Transaction::whereDate('created_at', today())->sum('total'),
            'open_shifts' => CashShift::where('status', 'open')->count(),
            'active_tables' => BilliardSession::where('status', 'active')->count(),
            'service_queue' => ServiceBooking::whereIn('status', [
                'received',
                'in progress',
            ])->count(),
            'low_stock' => InventoryItem::whereColumn('quantity', '<=', 'reorder_level')->count(),
            'alerts' => SecurityAlert::where('status', 'open')->count(),
        ];
    }

    /** Returns sales totals, reconciliation details, stock levels, and paginated transactions. */
    public function reportData(): array
    {
        return [
            'dailySales' => Transaction::whereDate('created_at', today())->sum('total'),
            'monthlySales' => Transaction::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
            'shiftSummary' => CashShift::with([
                'user',
                'transactions.user',
                'transactions.payments',
                'transactions.reversal.user',
                'transactions.voidRequests',
            ])
                ->latest()
                ->take(10)
                ->get(),
            'tableSessions' => BilliardSession::where('status', 'completed')
                ->whereMonth('ended_at', now()->month)
                ->count(),
            'serviceVolume' => ServiceBooking::whereMonth('created_at', now()->month)->count(),
            'inventoryLevels' => InventoryItem::orderBy('name')->get(),
            'transactions' => Transaction::with([
                'user',
                'payments',
                'reversal.user',
                'voidRequests',
            ])
                ->latest('created_at')
                ->paginate(25),
        ];
    }
}
