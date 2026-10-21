<?php

use App\Http\Controllers\CuecareController;
use App\Http\Controllers\VoidController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('cuecare.login'))->name('login');
Route::post('/login', [
    CuecareController::class,
    'login',
])->name('login.submit');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [
        CuecareController::class,
        'dashboard',
    ])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
    Route::get('/pos', [
        CuecareController::class,
        'pos',
    ])
        ->middleware('permission:pos.access')
        ->name('pos');
    Route::post('/pos/sales', [
        CuecareController::class,
        'sale',
    ])
        ->middleware('permission:pos.access')
        ->name('pos.sale');
    Route::get('/pos/receipt/{sale}', [
        CuecareController::class,
        'receipt',
    ])
        ->middleware('permission:pos.access')
        ->name('pos.receipt');
    Route::get('/menu', [
        CuecareController::class,
        'menu',
    ])
        ->middleware('permission:management.manage')
        ->name('menu');
    Route::post('/menu', [
        CuecareController::class,
        'storeMenu',
    ])
        ->middleware('permission:management.manage')
        ->name('menu.store');
    Route::patch('/menu/{item}', [
        CuecareController::class,
        'updateMenu',
    ])
        ->middleware('permission:management.manage')
        ->name('menu.update');
    Route::delete('/menu/{item}', [
        CuecareController::class,
        'destroyMenu',
    ])
        ->middleware('permission:management.manage')
        ->name('menu.destroy');
    Route::post('/pos/customize-usage', [
        CuecareController::class,
        'customizeUsage',
    ])
        ->middleware('permission:stock.customize_usage')
        ->name('pos.customize-usage');
    Route::get('/pos/order-requests/create', [
        CuecareController::class,
        'orderRequestForm',
    ])
        ->middleware('permission:orders.request')
        ->name('orders.request.form');
    Route::post('/pos/order-requests', [
        CuecareController::class,
        'storeOrderRequest',
    ])
        ->middleware('permission:orders.request')
        ->name('orders.request.store');
    Route::patch('/pos/order-requests/{order}/confirm', [
        CuecareController::class,
        'confirmOrder',
    ])
        ->middleware('permission:pos.access')
        ->name('orders.confirm');
    Route::patch('/pos/order-requests/{order}/reject', [
        CuecareController::class,
        'rejectOrder',
    ])
        ->middleware('permission:pos.access')
        ->name('orders.reject');
    Route::post('/shifts/open', [
        CuecareController::class,
        'openShift',
    ])
        ->middleware('permission:shift.manage')
        ->name('shift.open');
    Route::post('/shifts/close', [
        CuecareController::class,
        'closeShift',
    ])
        ->middleware('permission:shift.manage')
        ->name('shift.close');
    Route::get('/billiards', [
        CuecareController::class,
        'billiards',
    ])
        ->middleware('permission:billiards.access')
        ->name('billiards');
    Route::post('/billiards/tables', [
        CuecareController::class,
        'storeBilliardTable',
    ])
        ->middleware('permission:billiards.access')
        ->name('billiards.tables.store');
    Route::delete('/billiards/tables/{table}', [
        CuecareController::class,
        'destroyBilliardTable',
    ])
        ->middleware('permission:billiards.access')
        ->name('billiards.tables.destroy');
    Route::post('/billiards/tables/{table}/sessions', [
        CuecareController::class,
        'startBilliardSession',
    ])
        ->middleware('permission:billiards.access')
        ->name('billiards.sessions.start');
    Route::post('/billiards/sessions/{session}/end', [
        CuecareController::class,
        'endBilliardSession',
    ])
        ->middleware('permission:billiards.access')
        ->name('billiards.sessions.end');
    Route::get('/autocare', [
        CuecareController::class,
        'autocare',
    ])
        ->middleware('permission:autocare.service')
        ->name('autocare');
    Route::post('/autocare/bookings', [
        CuecareController::class,
        'storeAutocareBooking',
    ])
        ->middleware('permission:autocare.access')
        ->name('autocare.bookings.store');
    Route::patch('/autocare/bookings/{booking}/status', [
        CuecareController::class,
        'updateAutocareStatus',
    ])
        ->middleware('permission:management.manage')
        ->name('autocare.bookings.status');
    Route::delete('/autocare/bookings/{booking}', [
        CuecareController::class,
        'destroyAutocareBooking',
    ])
        ->middleware('permission:management.manage')
        ->name('autocare.bookings.destroy');
    Route::post('/autocare/staff', [
        CuecareController::class,
        'storeAutocareStaff',
    ])
        ->middleware('permission:management.manage')
        ->name('autocare.staff.store');
    Route::delete('/autocare/staff/{staff}', [
        CuecareController::class,
        'destroyAutocareStaff',
    ])
        ->middleware('permission:management.manage')
        ->name('autocare.staff.destroy');
    Route::get('/inventory', [
        CuecareController::class,
        'inventory',
    ])
        ->middleware('permission:inventory.view')
        ->name('inventory');
    Route::get('/inventory/create', fn () => redirect()->route('inventory'))
        ->middleware('permission:inventory.manage')
        ->name('inventory.create');
    Route::post('/inventory', [
        CuecareController::class,
        'storeInventory',
    ])
        ->middleware('permission:inventory.manage')
        ->name('inventory.store');
    Route::patch('/inventory/{item}', [
        CuecareController::class,
        'updateInventory',
    ])
        ->middleware('permission:inventory.manage')
        ->name('inventory.update');
    Route::delete('/inventory/{item}', [
        CuecareController::class,
        'destroyInventory',
    ])
        ->middleware('permission:inventory.manage')
        ->name('inventory.destroy');
    Route::get('/voids', [
        VoidController::class,
        'index',
    ])
        ->middleware('permission:void.approve')
        ->name('voids.index');
    Route::post('/transactions/{transaction}/void', [
        VoidController::class,
        'store',
    ])
        ->middleware('permission:void.request')
        ->name('voids.store');
    Route::post('/voids/{approval}/approve', [
        VoidController::class,
        'approve',
    ])
        ->middleware('permission:void.approve')
        ->name('voids.approve');
    Route::post('/voids/{approval}/deny', [
        VoidController::class,
        'deny',
    ])
        ->middleware('permission:void.approve')
        ->name('voids.deny');
    Route::get('/reports', [
        CuecareController::class,
        'reports',
    ])
        ->middleware('permission:reports.view')
        ->name('reports');
    Route::get('/reports/accounts/{account}/transactions', [
        CuecareController::class,
        'accountTransactions',
    ])
        ->middleware('permission:reports.view')
        ->name('reports.accounts.transactions');
    Route::get('/loyalty', [
        CuecareController::class,
        'loyalty',
    ])
        ->middleware('permission:loyalty.view')
        ->name('loyalty');
    Route::post('/loyalty/redeem', [
        CuecareController::class,
        'redeemLoyalty',
    ])
        ->middleware('permission:loyalty.redeem')
        ->name('loyalty.redeem');
    Route::get('/audit', [
        CuecareController::class,
        'audit',
    ])
        ->middleware('permission:audit.view')
        ->name('audit');
    Route::get('/users', fn () => redirect()->route('dashboard'))
        ->middleware('permission:management.manage')
        ->name('users.index');
    Route::get('/security', [
        CuecareController::class,
        'security',
    ])
        ->middleware('permission:security.view')
        ->name('security');
    Route::patch('/security/alerts/{alert}/acknowledge', [
        CuecareController::class,
        'acknowledgeAlert',
    ])
        ->middleware('permission:security.manage')
        ->name('security.alerts.acknowledge');
    Route::patch('/security/alerts/{alert}/close', [
        CuecareController::class,
        'closeAlert',
    ])
        ->middleware('permission:security.manage')
        ->name('security.alerts.close');
    Route::post('/logout', [
        CuecareController::class,
        'logout',
    ])->name('logout');
});
