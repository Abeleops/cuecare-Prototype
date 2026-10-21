<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditFilterRequest;
use App\Http\Requests\ConfirmFoodOrderRequest;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\MenuItemRequest;
use App\Http\Requests\RedeemLoyaltyRequest;
use App\Http\Requests\StoreAutocareBookingRequest;
use App\Http\Requests\StoreAutocareStaffRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\AuditLog;
use App\Models\BilliardSession;
use App\Models\BilliardTable;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\FoodOrder;
use App\Models\InventoryItem;
use App\Models\LoyaltyTransaction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Sale;
use App\Models\SecurityAlert;
use App\Models\SecurityEvent;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditService;
use App\Services\AutocareService;
use App\Services\BilliardsService;
use App\Services\LoyaltyService;
use App\Services\PosService;
use App\Services\ReportingService;
use App\Services\SecurityRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/** Handles the prototype's operational pages, transactions, and account sessions. */
class CuecareController extends Controller
{
    /** Returns the operational dashboard using the supplied reporting service. */
    public function dashboard(ReportingService $reports)
    {
        return view('cuecare.dashboard', [
            'metrics' => $reports->dashboardMetrics(),
        ]);
    }

    /** Returns the POS screen with sales, the current shift, menu items, and pending orders. */
    public function pos()
    {
        return view('cuecare.pos', [
            'sales' => Sale::with('items', 'transaction.reversal.user', 'transaction.voidRequests', 'payments')
                ->latest()
                ->paginate(12),
            'shift' => CashShift::where('user_id', auth()->id())
                ->where('status', 'open')
                ->first(),
            'customers' => Customer::all(),
            'menuItems' => MenuItem::with('recipes.inventoryItem')
                ->where('available', true)
                ->orderBy('name')
                ->get(),
            'pendingOrders' => FoodOrder::with([
                'items.menuItem',
                'requester',
            ])
                ->where('status', 'pending')
                ->latest()
                ->get(),
        ]);
    }

    /** Returns the authorized menu management screen and category options. */
    public function menu()
    {
        Gate::authorize('viewAny', MenuItem::class);

        return view('cuecare.menu', [
            'items' => MenuItem::with('category')
                ->orderBy('name')
                ->get(),
            'categories' => MenuCategory::orderBy('name')->get(),
        ]);
    }

    /** Creates a menu item from validated input and redirects back. */
    public function storeMenu(MenuItemRequest $request)
    {
        Gate::authorize('create', MenuItem::class);
        $data = $request->validated();
        $data['available'] = $request->boolean('available');

        if ($request->hasFile('picture')) {
            $data['picture_path'] = $request->file('picture')->store('menu-items', 'public');
        }

        unset($data['picture']);
        MenuItem::create($data);

        return back()->with('success', 'Menu item added.');
    }

    /** Updates the supplied menu item from validated input and redirects back. */
    public function updateMenu(MenuItemRequest $request, MenuItem $item)
    {
        Gate::authorize('update', $item);
        $data = $request->validated();
        $data['available'] = $request->boolean('available');

        if ($request->hasFile('picture')) {
            $data['picture_path'] = $request->file('picture')->store('menu-items', 'public');
        }

        unset($data['picture']);
        $item->update($data);

        return back()->with('success', 'Menu item updated.');
    }

    /** Deletes the authorized menu item and redirects back. */
    public function destroyMenu(MenuItem $item)
    {
        Gate::authorize('delete', $item);
        $item->delete();

        return back()->with('success', 'Menu item deleted.');
    }

    public function orderRequestForm()
    {
        return view('cuecare.order-request', [
            'menuItems' => MenuItem::where('available', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /** Saves positive-quantity order lines from validated input and redirects back. */
    public function storeOrderRequest(StoreOrderRequest $request)
    {
        Gate::authorize('create', FoodOrder::class);
        $data = $request->validated();
        $lines = collect($data['items'])
            ->filter(fn ($item) => (int) $item['quantity'] > 0)
            ->values();

        if ($lines->isEmpty()) {
            return back()
                ->withErrors([
                    'items' => 'Select at least one menu item.',
                ])
                ->withInput();
        }

        $items = MenuItem::whereIn('id', $lines->pluck('menu_item_id'))
            ->get()
            ->keyBy('id');
        $total = $lines->sum(fn ($item) => $items[$item['menu_item_id']]->price * $item['quantity']);
        $order = FoodOrder::create([
            'status' => 'pending',
            'total' => $total,
            'requested_by' => auth()->id(),
            'table_or_customer' => $data['table_or_customer'],
        ]);

        foreach ($lines as $item) {
            $order->items()->create([
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $items[$item['menu_item_id']]->price,
            ]);
        }

        return back()->with('success', 'Order request submitted to the Cashier.');
    }

    /** Charges the supplied pending order, records confirmation, and redirects back. */
    public function confirmOrder(ConfirmFoodOrderRequest $request, FoodOrder $order, PosService $pos)
    {
        Gate::authorize('confirm', $order);
        abort_if($order->status !== 'pending', 409, 'This order is no longer pending.');
        $data = $request->validated();
        $sale = $pos->createSale([
            'items' => $order->items->map(fn ($item) => [
                'menu_item_id' => $item->menu_item_id,
                'description' => $item->menuItem->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->all(),
            'payment_method' => $data['payment_method'],
            'amount_received' => $data['amount_received'] ?? null,
            'proof_of_payment' => $data['proof_of_payment'] ?? null,
        ], auth()->user());
        $order->update([
            'status' => 'confirmed',
            'sale_id' => $sale->id,
        ]);
        app(AuditService::class)->record(
            'food_order.confirmed',
            $order,
            [
                'status' => 'pending',
            ],
            [
                'status' => 'confirmed',
                'sale_id' => $sale->id,
            ],
        );

        return back()->with('success', 'Order confirmed and posted as a sale.');
    }

    /** Rejects the supplied pending order with a validated reason and redirects back. */
    public function rejectOrder(Request $request, FoodOrder $order)
    {
        Gate::authorize('confirm', $order);
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        abort_if($order->status !== 'pending', 409, 'This order is no longer pending.');
        $order->update([
            'status' => 'rejected',
            'rejected_reason' => $data['reason'],
        ]);
        app(AuditService::class)->record(
            'food_order.rejected',
            $order,
            [
                'status' => 'pending',
            ],
            [
                'status' => 'rejected',
                'rejected_reason' => $data['reason'],
            ],
        );

        return back()->with('success', 'Order request rejected.');
    }

    /** Charges validated sale data and redirects to a fresh POS sale. */
    public function sale(StoreSaleRequest $request, PosService $pos)
    {
        Gate::authorize('create', Sale::class);
        $data = $request->validated();
        $data['custom_usage'] = $request->session()->get('custom_recipe_usage', []);
        $sale = $pos->createSale($data, auth()->user());
        $request->session()->forget('custom_recipe_usage');

        return redirect()
            ->route('pos')
            ->with('success', 'Sale recorded. A new blank sale is ready.')
            ->with('receipt_id', $sale->id);
    }

    /** Stores validated recipe usage in the session for the next charge and redirects back. */
    public function customizeUsage(Request $request)
    {
        Gate::authorize('customizeUsage', InventoryItem::class);
        $data = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0',
        ]);
        $menu = MenuItem::with('recipes')->findOrFail($data['menu_item_id']);
        abort_unless(
            $menu->recipes->contains('inventory_item_id', (int) $data['inventory_item_id']),
            422,
            'This ingredient is not part of the recipe.',
        );
        $usage = $request->session()->get('custom_recipe_usage', []);
        $usage[$data['menu_item_id']][(string) $data['inventory_item_id']] = (float) $data['quantity'];
        $request->session()->put('custom_recipe_usage', $usage);
        app(AuditService::class)->record(
            'stock.usage_customized',
            $menu,
            [
                'menu_item_id' => $menu->id,
                'inventory_item_id' => $data['inventory_item_id'],
            ],
            [
                'menu_item_id' => $menu->id,
                'inventory_item_id' => $data['inventory_item_id'],
                'quantity' => (float) $data['quantity'],
            ],
        );

        return back()->with('success', 'Custom recipe usage saved for the next charge.');
    }

    public function receipt(Sale $sale)
    {
        return view('cuecare.receipt', compact('sale'));
    }

    /** Opens the current account's shift after validating starting cash and redirects back. */
    public function openShift(Request $r)
    {
        Gate::authorize('create', CashShift::class);
        $r->validate([
            'starting_cash' => 'nullable|numeric|min:0',
        ]);
        DB::transaction(function () use ($r) {
            User::whereKey(auth()->id())
                ->lockForUpdate()
                ->firstOrFail();
            abort_if(
                CashShift::where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->exists(),
                409,
                'You already have an open shift.',
            );
            CashShift::create([
                'user_id' => auth()->id(),
                'starting_cash' => $r->filled('starting_cash') ? $r->starting_cash : null,
                'opened_at' => now(),
            ]);
        });

        return back();
    }

    /** Reconciles the current account's open shift with validated cash and redirects back. */
    public function closeShift(Request $r, PosService $pos)
    {
        $r->validate([
            'counted_cash' => 'required|numeric|min:0',
        ]);
        $shift = CashShift::where('user_id', auth()->id())
            ->where('status', 'open')
            ->firstOrFail();
        Gate::authorize('close', $shift);
        $pos->closeShift($shift, $r->counted_cash);

        return back()->with('success', 'Shift closed and reconciled.');
    }

    /** Returns billiard tables with active sessions and customer options. */
    public function billiards()
    {
        return view('cuecare.billiards', [
            'tables' => BilliardTable::with([
                'sessions' => fn ($q) => $q->where('status', 'active'),
            ])
                ->orderBy('id')
                ->get(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    /** Starts a session on the supplied available table and redirects back. */
    public function startBilliardSession(Request $r, BilliardTable $table, BilliardsService $service)
    {
        $r->validate([
            'customer_id' => 'nullable|exists:customers,id',
        ]);
        abort_if($table->status !== 'available', 409, 'This table is not available.');
        $service->start($table, $r->customer_id);

        return back()->with('success', "{$table->name} session started.");
    }

    /** Ends the supplied active session through the billing service and redirects back. */
    public function endBilliardSession(BilliardSession $session, BilliardsService $service)
    {
        abort_if($session->status !== 'active', 409, 'This session is already closed.');
        $service->end($session);

        return back()->with('success', 'Session ended and posted to the transaction ledger.');
    }

    /** Creates an authorized billiard table from validated rates and redirects back. */
    public function storeBilliardTable(Request $r)
    {
        $table = new BilliardTable;
        Gate::authorize('create', $table);
        $data = $r->validate([
            'name' => 'required|string|max:100',
            'hourly_rate' => 'required|numeric|min:0',
            'price_per_half_hour' => 'required|numeric|min:0',
        ]);
        $table->create($data + [
            'status' => 'available',
        ]);

        return back()->with('success', 'Billiards table added.');
    }

    /** Deletes the supplied authorized table when no active session exists and redirects back. */
    public function destroyBilliardTable(BilliardTable $table)
    {
        Gate::authorize('delete', $table);
        abort_if(
            $table->sessions()
                ->where('status', 'active')
                ->exists(),
            409,
            'End the active session before deleting this table.',
        );
        $table->delete();

        return back()->with('success', 'Billiards table deleted.');
    }

    /** Returns autocare bookings, active catalog services, and unlocked staff accounts. */
    public function autocare()
    {
        return view('cuecare.autocare', [
            'bookings' => ServiceBooking::with([
                'vehicle',
                'serviceCatalog',
                'transaction.payments',
                'statusHistories',
                'assignedStaff',
            ])
                ->latest()
                ->get(),
            'serviceCatalog' => ServiceCatalog::where('active', true)
                ->orderBy('name')
                ->get(),
            'staff' => User::where('role', 'autocare_staff')
                ->whereNull('locked_at')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /** Creates and charges a validated autocare booking and redirects back. */
    public function storeAutocareBooking(StoreAutocareBookingRequest $request, AutocareService $service)
    {
        Gate::authorize('create', ServiceBooking::class);
        $service->create($request->validated(), auth()->user());

        return back()->with('success', 'Autocare service saved and posted to the transaction ledger.');
    }

    /** Advances the supplied booking to a validated status and redirects back. */
    public function updateAutocareStatus(Request $request, ServiceBooking $booking, AutocareService $service)
    {
        Gate::authorize('update', $booking);
        $data = $request->validate([
            'status' => 'required|in:received,in progress,ready for pickup,released',
        ]);
        $service->updateStatus($booking, $data['status'], auth()->user());

        return back()->with('success', 'Vehicle status updated with a system timestamp.');
    }

    /** Deletes the supplied authorized autocare booking and redirects back. */
    public function destroyAutocareBooking(ServiceBooking $booking)
    {
        Gate::authorize('delete', $booking);
        $booking->delete();

        return back()->with('success', 'Autocare service deleted.');
    }

    /** Creates a validated autocare staff account, assigns its role, and redirects back. */
    public function storeAutocareStaff(StoreAutocareStaffRequest $request)
    {
        abort_unless(auth()->user()->hasPermissionTo('management.manage'), 403);
        $staff = User::create($request->validated() + [
            'role' => 'autocare_staff',
            'station' => 'Autocare',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'autocare_staff',
            'guard_name' => 'web',
        ]);
        $staff->syncRoles([$role]);

        return back()->with('success', 'Autocare staff added.');
    }

    /** Deletes an authorized, unassigned autocare staff account and redirects back. */
    public function destroyAutocareStaff(User $staff)
    {
        abort_unless(auth()->user()->hasPermissionTo('management.manage'), 403);
        abort_unless($staff->role === 'autocare_staff', 404);
        abort_if(
            $staff->assignedAutocareBookings()->exists(),
            409,
            'Reassign this staff member before deleting.',
        );
        $staff->delete();

        return back()->with('success', 'Autocare staff deleted.');
    }

    public function inventory()
    {
        return view('cuecare.inventory', [
            'items' => InventoryItem::orderBy('name')->get(),
        ]);
    }

    /** Returns paginated customer loyalty records and recent points activity. */
    public function loyalty()
    {
        return view('cuecare.loyalty', [
            'customers' => Customer::withCount('loyaltyTransactions')
                ->orderBy('name')
                ->paginate(20),
            'history' => LoyaltyTransaction::with([
                'customer',
                'user',
            ])
                ->latest()
                ->paginate(20),
        ]);
    }

    /** Redeems validated points through the loyalty service and redirects back. */
    public function redeemLoyalty(RedeemLoyaltyRequest $request, LoyaltyService $service)
    {
        Gate::authorize('redeem', Customer::class);
        $data = $request->validated();
        $service->redeem(
            Customer::findOrFail($data['customer_id']),
            $data['points'],
            auth()->user(),
            $data['notes'] ?? null,
        );

        return back()->with('success', 'Loyalty points redeemed.');
    }

    /** Returns the authorized security alert and event lists. */
    public function security()
    {
        Gate::authorize('viewAny', SecurityAlert::class);

        return view('cuecare.security', [
            'alerts' => SecurityAlert::with('rule')
                ->latest()
                ->paginate(20),
            'events' => SecurityEvent::with('user')
                ->latest()
                ->paginate(20),
        ]);
    }

    /** Acknowledges the supplied open alert and redirects back. */
    public function acknowledgeAlert(SecurityAlert $alert, SecurityRuleService $service)
    {
        Gate::authorize('update', $alert);
        abort_if($alert->status === 'closed', 409, 'Closed alerts cannot be reopened.');
        $service->changeAlertStatus($alert, 'acknowledged');

        return back()->with('success', 'Security alert acknowledged.');
    }

    /** Closes the supplied authorized alert and redirects back. */
    public function closeAlert(SecurityAlert $alert, SecurityRuleService $service)
    {
        Gate::authorize('update', $alert);
        $service->changeAlertStatus($alert, 'closed');

        return back()->with('success', 'Security alert closed.');
    }

    /** Creates an authorized inventory item from validated input and redirects back. */
    public function storeInventory(InventoryItemRequest $request)
    {
        Gate::authorize('create', InventoryItem::class);
        InventoryItem::create($request->inventoryData());

        return back()->with('success', 'Inventory item added.');
    }

    /** Updates the supplied authorized inventory item and redirects back. */
    public function updateInventory(InventoryItemRequest $request, InventoryItem $item)
    {
        Gate::authorize('update', $item);
        $item->update($request->inventoryData());

        return back()->with('success', 'Inventory item updated.');
    }

    /** Deletes the supplied authorized item when no recipes reference it and redirects back. */
    public function destroyInventory(InventoryItem $item)
    {
        Gate::authorize('delete', $item);
        abort_if($item->recipes()->exists(), 409, 'Remove recipe links before deleting this item.');
        $item->delete();

        return back()->with('success', 'Inventory item deleted.');
    }

    public function reports(ReportingService $reports)
    {
        return view('cuecare.reports', $reports->reportData());
    }

    /** Returns authorized transaction history for the supplied account. */
    public function accountTransactions(User $account)
    {
        abort_unless(auth()->user()->hasPermissionTo('reports.view'), 403);
        $transactions = Transaction::with([
            'user',
            'payments',
            'reversal.user',
            'voidRequests',
        ])
            ->where('user_id', $account->id)
            ->latest('created_at')
            ->paginate(25);

        return view('cuecare.account-transactions', compact('account', 'transactions'));
    }

    /** Returns authorized audit entries filtered by validated account, action, and dates. */
    public function audit(AuditFilterRequest $request)
    {
        Gate::authorize('viewAny', AuditLog::class);
        $filters = $request->validated();
        $query = AuditLog::with('user')
            ->latest('created_at')
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', 'like', '%'.$action.'%'))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));

        return view('cuecare.audit', [
            'logs' => $query->paginate(25)->withQueryString(),
            'users' => User::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    /** Returns the generic operational view for the supplied supported module name. */
    public function module(string $module)
    {
        $data = [
            'billiards' => BilliardTable::withCount([
                'sessions' => fn ($q) => $q->where('status', 'active'),
            ])->get(),
            'autocare' => ServiceBooking::with('vehicle')
                ->latest()
                ->get(),
            'food' => FoodOrder::latest()
                ->with('items')
                ->get(),
            'inventory' => InventoryItem::latest()->get(),
            'security' => SecurityAlert::with('rule')
                ->latest()
                ->get(),
        ];
        abort_unless(isset($data[$module]), 404);

        return view('cuecare.module', compact('module', 'data'));
    }

    /** Authenticates validated credentials, audits the result, and redirects to an allowed page. */
    public function login(Request $r)
    {
        $credentials = $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials)) {
            app(SecurityRuleService::class)->record(
                'failed_login',
                User::where('email', $credentials['email'])->value('id'),
            );
            $failed = User::where('email', $credentials['email'])->first();

            if ($failed) {
                app(AuditService::class)->record('login.failed', $failed, null, [
                    'email' => $failed->email,
                ]);
            }

            return back()
                ->withErrors([
                    'email' => 'Invalid demo credentials.',
                ])
                ->onlyInput('email');
        }

        $r->session()->regenerate();
        $user = auth()->user();
        app(AuditService::class)->record('login.succeeded', $user, null, [
            'email' => $user->email,
        ]);

        if ($user->hasPermissionTo('dashboard.view')) {
            return redirect()->route('dashboard');
        }

        foreach ([
            [
                'pos',
                'pos.access',
            ],
            [
                'billiards',
                'billiards.access',
            ],
            [
                'autocare',
                'autocare.access',
            ],
            [
                'inventory',
                'inventory.view',
            ],
        ] as [$route, $permission]) {
            if ($user->hasPermissionTo($permission)) {
                return redirect()->route($route);
            }
        }

        return redirect('/');
    }

    /** Audits logout, closes the account's open shifts, and redirects to the login page. */
    public function logout()
    {
        if (auth()->check()) {
            app(AuditService::class)->record('logout', $user = auth()->user(), null, [
                'email' => $user->email,
            ]);
            CashShift::where('user_id', auth()->id())
                ->where('status', 'open')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'close_reason' => 'auto-closed on logout',
                    'counted_cash' => null,
                    'variance' => null,
                ]);
        }

        auth()->logout();

        return redirect('/');
    }
}
