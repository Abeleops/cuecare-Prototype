<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\BilliardTable;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Recipe;
use App\Models\SecurityRule;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Provide Database Seeder data.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $matrix = [
            'shift.manage' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'void.request' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'void.approve' => [
                'admin',
                'manager',
            ],
            'loyalty.confirm' => [
                'admin',
                'manager',
            ],
            'dashboard.overview' => [
                'admin',
                'manager',
                'owner',
            ],
            'dashboard.view' => [
                'admin',
                'manager',
                'owner',
                'cashier',
                'autocare_staff',
                'security_officer',
            ],
            'pos.access' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'billiards.access' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'autocare.access' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'autocare.service' => [
                'admin',
                'manager',
                'owner',
                'cashier',
                'autocare_staff',
            ],
            'inventory.view' => [
                'admin',
                'manager',
                'owner',
                'cashier',
                'autocare_staff',
            ],
            'inventory.manage' => [
                'admin',
            ],
            'inventory.approve' => [
                'admin',
                'manager',
                'owner',
            ],
            'orders.request' => [
                'autocare_staff',
            ],
            'stock.customize_usage' => [
                'admin',
            ],
            'loyalty.view' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'loyalty.redeem' => [
                'admin',
                'manager',
                'owner',
                'cashier',
            ],
            'reports.view' => [
                'admin',
                'manager',
                'owner',
            ],
            'management.manage' => [
                'admin',
                'manager',
                'owner',
            ],
            'security.view' => [
                'admin',
                'security_officer',
            ],
            'security.manage' => [
                'admin',
                'security_officer',
            ],
            'audit.view' => [
                'admin',
                'manager',
            ],
        ];
        $rolePermissions = [];
        foreach ($matrix as $permission => $roles) {
            $p = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
            foreach ($roles as $roleName) {
                $rolePermissions[$roleName][] = $p;
            }
        }
        foreach ($rolePermissions as $roleName => $permissions) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($permissions);
        }
        $system = User::updateOrCreate([
            'email' => 'system@cuecare.test',
        ], [
            'name' => 'System Audit Actor',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'station' => 'System',
        ]);
        $system->syncRoles([
            'admin',
        ]);
        foreach ([
            'admin',
            'manager',
            'owner',
            'cashier',
            'autocare_staff',
            'security_officer',
        ] as $role) {
            $user = User::updateOrCreate([
                'email' => "{$role}@cuecare.test",
            ], [
                'name' => ucwords(str_replace('_', ' ', $role)),
                'password' => bcrypt('password'),
                'role' => $role,
                'station' => 'Main Branch',
            ]);
            $user->syncRoles([
                $role,
            ]);
        }
        $autocareStaff = [];
        foreach (range(1, 3) as $number) {
            $staff = User::updateOrCreate([
                'email' => "autocare{$number}@cuecare.test",
            ], [
                'name' => "Autocare Staff {$number}",
                'password' => bcrypt('password'),
                'role' => 'autocare_staff',
                'station' => 'Autocare',
            ]);
            $staff->syncRoles([
                'autocare_staff',
            ]);
            $autocareStaff[] = $staff->id;
        }
        $customer = Customer::updateOrCreate([
            'phone' => '09171234567',
        ], [
            'name' => 'Maria Santos',
            'loyalty_points' => 120,
        ]);
        foreach ([
            'Table 1',
            'Table 2',
            'Table 3',
            'Table 4',
            'Table 5',
            'Table 6',
        ] as $name) {
            BilliardTable::updateOrCreate([
                'name' => $name,
            ], [
                'hourly_rate' => 200,
                'price_per_half_hour' => 100,
                'status' => 'available',
            ]);
        }
        $cat = MenuCategory::updateOrCreate([
            'name' => 'Best Sellers',
        ]);
        foreach ([
            [
                'name' => 'Beef Nachos',
                'price' => 280,
            ],
            [
                'name' => 'Iced Tea',
                'price' => 90,
            ],
            [
                'name' => 'Chicken Wings',
                'price' => 320,
            ],
        ] as $i) {
            MenuItem::updateOrCreate([
                'menu_category_id' => $cat->id,
                'name' => $i['name'],
            ], $i + [
                'menu_category_id' => $cat->id,
                'available' => true,
            ]);
        }
        foreach ([
            [
                'name' => 'Cooking Oil',
                'quantity' => 8,
                'reorder_level' => 10,
                'unit' => 'L',
            ],
            [
                'name' => 'Chicken',
                'quantity' => 25,
                'reorder_level' => 10,
                'unit' => 'kg',
            ],
            [
                'name' => 'Tea Syrup',
                'quantity' => 4,
                'reorder_level' => 5,
                'unit' => 'bottle',
            ],
        ] as $i) {
            InventoryItem::updateOrCreate([
                'name' => $i['name'],
            ], $i);
        }
        $oil = InventoryItem::where('name', 'Cooking Oil')->first();
        $chicken = InventoryItem::where('name', 'Chicken')->first();
        $syrup = InventoryItem::where('name', 'Tea Syrup')->first();
        $wings = MenuItem::where('name', 'Chicken Wings')->first();
        $icedTea = MenuItem::where('name', 'Iced Tea')->first();
        if ($wings && $chicken) {
            Recipe::updateOrCreate([
                'menu_item_id' => $wings->id,
                'inventory_item_id' => $chicken->id,
            ], [
                'quantity' => 0.25,
            ]);
        }
        if ($wings && $oil) {
            Recipe::updateOrCreate([
                'menu_item_id' => $wings->id,
                'inventory_item_id' => $oil->id,
            ], [
                'quantity' => 0.05,
            ]);
        }
        if ($icedTea && $syrup) {
            Recipe::updateOrCreate([
                'menu_item_id' => $icedTea->id,
                'inventory_item_id' => $syrup->id,
            ], [
                'quantity' => 0.1,
            ]);
        }
        $vehicle = Vehicle::updateOrCreate([
            'plate_number' => 'ABC-1234',
        ], [
            'customer_id' => $customer->id,
            'vehicle_type' => 'sedan',
            'model' => 'Vios',
        ]);
        ServiceBooking::updateOrCreate([
            'vehicle_id' => $vehicle->id,
            'requested_service' => 'Oil change + inspection',
        ], [
            'customer_id' => $customer->id,
            'scheduled_date' => today(),
            'estimated_cost' => 1800,
            'status' => 'received',
        ]);
        foreach ([
            [
                'name' => 'Oil change + inspection',
                'price' => 1800,
            ],
            [
                'name' => 'Full detailing',
                'price' => 2500,
            ],
            [
                'name' => 'Brake service',
                'price' => 3200,
            ],
        ] as $service) {
            ServiceCatalog::updateOrCreate([
                'name' => $service['name'],
            ], $service + [
                'active' => true,
            ]);
        }
        $demoBooking = ServiceBooking::where('vehicle_id', $vehicle->id)
            ->where('requested_service', 'Oil change + inspection')
            ->first();
        if ($demoBooking) {
            $demoBooking->assignedStaff()->sync($autocareStaff);
        }
        SecurityRule::updateOrCreate([
            'name' => 'Brute force protection',
        ], [
            'event_type' => 'failed_login',
            'threshold' => 5,
            'window_minutes' => 10,
            'action' => 'lock_account',
            'active' => true,
        ]);
        AuditLog::firstOrCreate([
            'action' => 'seed.demo',
            'auditable_type' => User::class,
            'auditable_id' => $system->id,
        ], [
            'user_id' => $system->id,
            'old_values' => null,
            'new_values' => [
                'message' => 'Demo audit entry',
            ],
            'ip_address' => null,
            'user_agent' => 'CUECARE seeder',
            'created_at' => now(),
        ]);
    }
}
