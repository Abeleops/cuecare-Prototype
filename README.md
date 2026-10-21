# CUECARE prototype

## 1. Project overview

CUECARE is a back-office point-of-sale (POS) and operations platform for a business that combines billiards/KTV, food and beverage, and an autocare service. This prototype is admin-side only: it has no customer-facing pages and no QR ordering flow.

The seeded roles and their current scope are:

| Role | Status and purpose |
|---|---|
| Admin | Implemented. Full operational access, including management, inventory, void approval, audit, and security controls. |
| Cashier (`cashier`) | Implemented. POS, shifts, billiards, and permitted order/payment work. |
| Autocare Staff (`autocare_staff`) | Implemented. Dashboard, autocare service work, inventory viewing, and food-order requests. |
| Manager (`manager`) | Documented/seeded, but a dedicated manager workflow is not confirmed as implemented. |
| Security Officer (`security_officer`) | Documented/seeded for security viewing and management, but a dedicated security-officer workflow is not confirmed as implemented. |
| Owner (`owner`) | Seeded in the permission matrix; a separate owner workflow is not documented in the current prototype. |

## 2. Tools and technologies

| Tool | Version | Purpose |
|---|---|---|
| PHP | `^8.2` | Server runtime. |
| Laravel | `^12.0` | Web application framework. |
| MySQL | Needs verification | Intended local database settings are database `laravel` and host `127.0.0.1`; the committed example environment currently defaults to SQLite. |
| Spatie Laravel Permission | `^6.21` | Roles and permissions. |
| Tailwind CSS | `^4.0.0` | Frontend styling. |
| Alpine.js | Needs verification | Some views use Alpine-style interactions, but it is not declared in `package.json`. |
| Vite | `^6.0.11` | Frontend asset development and builds. |
| Laravel Pint | `^1.13` | PHP code formatting. |
| PHPUnit | `^11.5.3` | Feature and unit tests. |
| Laravel Tinker | `^2.10.1` | Interactive Laravel shell. |
| Laravel Sail | `^1.41` | Development container tooling. |
| Laravel Pail | `^1.2.2` | Development log viewing. |
| FakerPHP | `^1.23` | Test data generation. |
| Mockery | `^1.6` | Test doubles. |
| Collision | `^8.6` | Console exception display. |
| Axios | `^1.7.4` | JavaScript HTTP client dependency. |
| Laravel Vite Plugin | `^1.2.0` | Laravel integration for Vite. |
| Composer | Current installed version | PHP package manager. |
| npm | Current installed version | JavaScript package manager. |

Laravel Breeze is not declared in `composer.json`; the current authentication implementation needs verification before this README can describe it as Breeze. The current views use Blade and Tailwind.

## 3. How the system works

### Modules

| Module | What it does | Status |
|---|---|---|
| Authentication and dashboard | Login, logout, dashboard metrics, account locking events, and role-aware navigation. | Built |
| POS and food and beverage | Menu items, sales, receipts, payments, stock deduction, and food-order requests/confirmation. | Built |
| Billiards | Tables, rates, active sessions, and posting completed sessions to the ledger. | Built |
| Autocare | Service catalog, bookings, staff assignment, status timeline, and service transactions. | Built |
| Inventory | Items, recipes, stock movement, low-stock reporting, and admin management. | Built/partial: the inventory approval service exists, but no approval route is exposed in `routes/web.php`. |
| Loyalty | Point earning and redemption records. | Built/partial: the seeded `loyalty.confirm` permission is not used by the current redemption route. |
| Voids and approvals | Void requests, approval/denial, reversing ledger entries, and approval audit events. | Built |
| Reports and reconciliation | Ledger-based sales, shift summaries, account transactions, inventory levels, and variance. | Built |
| Audit trail | Append-only records of operational changes and before/after values. | Built |
| Security events | Rule-based event correlation, alerts, acknowledgement, closure, and account locking for the seeded brute-force rule. | Built |

### Shared transaction ledger

Sales, billiards sessions, and autocare services write financial records to `transactions`. Reports and shift reconciliation read from the same ledger. Ledger rows are intended to be append-only: the original transaction remains unchanged after posting. A void therefore creates a signed reversing entry with the opposite amount and links it to the original. This preserves the history and keeps reports auditable.

Audit logs and stock movements also guard against updates or deletes where the model explicitly requires append-only history.

### Roles and access

The main permission groups are `dashboard.view`, `pos.access`, `shift.manage`, `billiards.access`, `autocare.service`, `autocare.access`, `inventory.view`, `inventory.manage`, `inventory.approve`, `orders.request`, `void.request`, `void.approve`, `reports.view`, `management.manage`, `loyalty.view`, `loyalty.redeem`, `audit.view`, `security.view`, and `security.manage`. The complete role-to-permission matrix is in `database/seeders/DatabaseSeeder.php` and is mirrored by the fallback matrix in `app/Models/User.php`.

Access is enforced in routes with permission middleware, in Policies/Gate checks, and in views with authorization checks. For example, Admin-only management and inventory actions use `management.manage` or `inventory.manage`.

### Approval workflow

Transaction voids require a request, then approval or denial by a user with `void.approve`. A requester cannot approve their own request; the Policy compares the requester and reviewer IDs. Approval creates a reversing transaction and denial requires a reason. Inventory approval request/review services and the `inventory.approve` permission exist, but the current route list does not expose an inventory approval screen. The seeded `loyalty.confirm` permission also exists, but its use is not confirmed in the current redemption flow.

### Audit trail

The application records actions such as login/logout, menu and inventory changes, transaction activity, approval decisions, security events, and role/permission changes. Records are stored in `audit_logs` and viewed through `/audit`, which requires `audit.view`; the seeded Admin and Manager roles have that permission. Audit timestamps are stored and normalized in UTC, and the audit view displays them in 12-hour format using `M d, Y g:i:s A`.

### Security events

`SecurityRuleService` records events, counts matching events during each active rule window, and creates alerts when a threshold is reached. The seeder creates a brute-force rule for five `failed_login` events within ten minutes with the `lock_account` action. The user observer handles the automatic account-lock response; Admin and Security Officer accounts have security-view/manage permissions. Alert acknowledgement and closure are admin-controlled through the security routes. Exact client policy for other automatic responses needs verification.

### Shift control

A user must have an open cash shift before a POS charge can be created. Closing a shift records expected cash, counted cash, and variance. Logging out records the logout and automatically closes any open shift with the reason `auto-closed on logout`.

### Payments

The POS accepts `cash`, `card`, and `e-wallet`; the UI labels the e-wallet option as E-wallet (GCash). Cash requires the amount received and must cover the sale total. E-wallet payment requires an uploaded proof-of-payment file (`jpg`, `jpeg`, or `png`); the file is stored on the local private disk. Card has no proof upload rule in the current request validation.

### Client-dependent configuration

`config/cuecare.php` is not present in the current application, so no config flags or defaults can be confirmed. The discount limit is not present in the current transaction schema. Loyalty redemption approval and blind cash count are client-dependent items named in the change request, but their config keys and defaults need verification.

### Request flow

```text
browser → route → middleware → controller → Form Request → Policy → service → model → database
```

## 4. Folder structure

```text
app/Services          Business workflows and transaction logic
app/Policies          Per-model authorization rules
app/Observers         Audit and model event handlers
app/Http/Requests     Input validation classes
app/Http/Controllers  Web request handlers
database/migrations   Database schema changes
database/seeders      Roles, permissions, demo data, and seed rules
database/factories    Test model factories
resources/views       Blade pages and layouts
routes                Web route definitions
tests/Feature         End-to-end feature and access tests
```

## 5. Setup instructions

Requirements: PHP `8.2` or newer, Composer, Node.js with npm, MySQL, and a local server such as XAMPP or Laragon.

Run these commands from the Laravel application directory:

```text
cd backend/New folder
```

1. Clone the repository.

2. Install PHP dependencies:

```text
composer install
```

3. Create the environment file and set the database values:

```text
cp .env.example .env
```

Set `DB_DATABASE=laravel`, `DB_HOST=127.0.0.1`, and the local MySQL username and password. Note that the committed `.env.example` currently has SQLite as its active default, so change `DB_CONNECTION` to the MySQL driver used by the local setup.

4. Generate the application key:

```text
php artisan key:generate
```

5. Install frontend dependencies:

```text
npm install
```

6. Run migrations and seed demo data:

```text
php artisan migrate --seed
```

7. In a second terminal, start Vite:

```text
npm run dev
```

8. Start the Laravel server and open the printed local URL:

```text
php artisan serve
```

For local development only, `migrate:fresh --seed` drops and recreates the database, so it deletes all existing data:

```text
php artisan migrate:fresh --seed
```

## 6. Demo accounts

All seeded accounts use password `password`.

| Role | Email | Password | What it can do |
|---|---|---|---|
| Admin | `admin@cuecare.test` | `password` | Full implemented admin operations, management, approvals, audit, and security controls. |
| Cashier | `cashier@cuecare.test` | `password` | POS, shifts, billiards, permitted order/payment work, and void requests. |
| Autocare Staff | `autocare_staff@cuecare.test` | `password` | Autocare work, dashboard, inventory viewing, and order requests. |
| Manager | `manager@cuecare.test` | `password` | Seeded management/reporting/approval permissions; dedicated workflow needs verification. |
| Owner | `owner@cuecare.test` | `password` | Seeded owner permissions; separate workflow needs verification. |
| Security Officer | `security_officer@cuecare.test` | `password` | Security viewing and management permissions. |
| System audit actor | `system@cuecare.test` | `password` | Seeder-created system account used for the demo audit entry. |
| Autocare Staff 1–3 | `autocare1@cuecare.test` through `autocare3@cuecare.test` | `password` | Seeded autocare staff accounts assigned to the demo booking. |

These are demo accounts only. Change or disable them before any real use.

## 7. Testing

Run all tests:

```text
php artisan test
```

Run one test class or matching group:

```text
php artisan test --filter=ClassName
```

The feature tests cover role access, expected `403` responses, login redirects, POS and ledger flows, audit rows, approvals, security rules, inventory behavior, and append-only guards. The test filenames also cover autocare, billiards, loyalty, reports, stock deduction, and module-specific access/corrections.

To test a new protected route, create a user with a role that lacks the route's permission, send the request while authenticated, and assert a `403` response. Then create or use a permitted role and assert the successful response or redirect. This checks both sides of the permission boundary.

Check formatting without changing files:

```text
./vendor/bin/pint --test
```

## 8. How to demo the system

1. Admin:
   1. Log in as `admin@cuecare.test`.
   2. Check the dashboard metrics.
   3. Open Menu and create a menu item.
   4. Approve a pending void request.
   5. Open the audit log and review the recorded actions.

2. Cashier:
   1. Log in as `cashier@cuecare.test`.
   2. Start a shift and enter starting cash if desired.
   3. Make a sale using cash, card, or E-wallet (GCash).
   4. Open Billiards and start a table session.
   5. Request a void for a posted transaction.

3. Autocare Staff:
   1. Log in as `autocare_staff@cuecare.test`.
   2. Submit an autocare order request.
   3. Confirm the access limits: the account can request orders and work with autocare functions, but cannot use Admin-only management, inventory changes, void approval, reports, or audit viewing.

## 9. Troubleshooting

| Problem | Fix |
|---|---|
| `SQLSTATE connection refused` | Start MySQL and check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. |
| `Vite manifest not found` | Run `npm run dev`, or create a production manifest with `npm run build`. |
| `Class not found` | Run `composer dump-autoload`, then retry. |
| `403 after login` | Check the user's role and permissions in `database/seeders/DatabaseSeeder.php`; also check the route middleware and Policy. |
| `table already exists` | On local data only, run `php artisan migrate:fresh --seed`. This deletes the database contents. |
| Assets or frontend changes do not appear | Keep `npm run dev` running, or run `npm run build` and refresh the browser. |
| Login does not work after reseeding | Confirm the seeded email and password, then run `php artisan migrate:fresh --seed` if the local database is disposable. |

## 10. Planned, not yet built

The change-request material referenced for this section is not present in the synced `sources/` directory. Based only on the current code and request, these items are planned or need confirmation:

- Customer-facing pages — planned.
- QR ordering — planned.
- A dedicated Manager workflow — planned.
- A dedicated Security Officer workflow and final security response policy — planned.
- A client-confirmed `config/cuecare.php` flag set, including loyalty redemption approval and blind cash count — planned.
- An exposed inventory approval route and screen — planned.
- Final handling for the seeded `loyalty.confirm` permission — planned.

## 11. Open items to confirm with the client

- What “turnover” means in the business process and which records should contribute to it.
- Whether loyalty redemption requires approval, and the intended config key and default.
- Whether blind cash count is required, and the intended config key and default.
- Whether MySQL is the required runtime database for the prototype or whether SQLite remains supported.
- Whether Manager, Owner, and Security Officer need dedicated pages and workflows beyond their seeded permissions.
- Whether the current private proof-of-payment storage and accepted file types meet the client’s operational policy.

## 12. Glossary

- **Ledger:** A shared, ordered record of financial transactions used by sales, reports, and reconciliation.
- **Append-only:** History can be added to but existing records are not edited or deleted.
- **Reversing entry:** A new transaction with the opposite amount used to cancel the effect of an earlier entry while preserving history.
- **Policy:** A class that answers whether a user may perform an action on a specific model record.
- **Form Request:** A Laravel class that validates and authorizes incoming form data before the controller uses it.
- **RBAC:** Role-based access control; permissions are assigned through roles.
- **Separation of duties:** A requester and reviewer must be different people for a controlled action.
- **Shift reconciliation:** Comparing expected cash with counted cash when a shift closes.
- **Variance:** The difference between counted cash and expected cash.
- **E-wallet (GCash):** The current UI label for the `e-wallet` payment method.
