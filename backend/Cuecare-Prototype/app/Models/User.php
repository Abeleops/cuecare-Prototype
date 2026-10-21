<?php

namespace App\Models;

use App\Observers\UserObserver;
use App\Services\AuditService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

/** Represents an account with role checks, permission fallback, and audit hooks. */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles {
        hasPermissionTo as protected spatieHasPermissionTo;
    }

    /** @var list<string> Attributes that are mass assignable. */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'station',
        'locked_at',
    ];

    /** @var list<string> Attributes hidden from serialization. */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::observe(UserObserver::class);

        foreach ([
            RoleAttached::class => 'role.attached',
            RoleDetached::class => 'role.detached',
            PermissionAttached::class => 'permission.attached',
            PermissionDetached::class => 'permission.detached',
        ] as $event => $action) {
            \Event::listen($event, function (object $event) use ($action): void {
                app(AuditService::class)->recordPermissionChange(
                    $action,
                    $event->model,
                    $event->rolesOrIds ?? $event->permissionsOrIds ?? [],
                );
            });
        }
    }

    /** Returns whether the supplied role matches through stored roles or the account fallback. */
    public function hasRole($role, $guardName = null): bool
    {
        if (is_array($role)) {
            return collect($role)->contains(fn ($item) => $this->hasRole($item, $guardName));
        }

        if ($role instanceof Role) {
            $role = $role->getRoleName();
        }

        $roleName = (string) $role;
        $guard = $guardName ?: $this->getDefaultGuardName();

        return $this->roles()
            ->where('roles.name', $roleName)
            ->where('roles.guard_name', $guard)
            ->exists() || $this->role === $roleName || $this->role === 'admin';
    }

    /** Returns whether the supplied permission is granted by Spatie or the fallback matrix. */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        try {
            if ($this->spatieHasPermissionTo($permission, $guardName)) {
                return true;
            }
        } catch (PermissionDoesNotExist) {
            // Tests and fresh installs may not be seeded yet.
        }

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

        return in_array($this->role, $matrix[$permission] ?? [], true);
    }

    public function assignedAutocareBookings()
    {
        return $this->belongsToMany(ServiceBooking::class, 'service_booking_staff');
    }

    /** @return array<string, string> Attribute casts for account dates and passwords. */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_at' => 'datetime',
        ];
    }
}
