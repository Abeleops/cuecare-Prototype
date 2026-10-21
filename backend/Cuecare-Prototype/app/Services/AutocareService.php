<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Creates charged autocare bookings and advances their system-managed status. */
class AutocareService
{
    /** Creates a booking from supplied service and payment data, returning the loaded booking. */
    public function create(array $data, $user): ServiceBooking
    {
        return DB::transaction(function () use ($data, $user) {
            $shift = CashShift::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'shift' => 'Open a cashier shift before charging an autocare service. Start one from the POS screen.',
                ]);
            }

            $catalog = ServiceCatalog::whereKey($data['service_catalog_id'])
                ->where('active', true)
                ->firstOrFail();
            $customer = Customer::updateOrCreate([
                'phone' => $data['owner_contact'],
            ], [
                'name' => $data['owner_name'],
            ]);
            $vehicle = Vehicle::updateOrCreate([
                'plate_number' => strtoupper($data['plate_number']),
            ], [
                'customer_id' => $customer->id,
                'vehicle_type' => $data['vehicle_type'],
                'model' => $data['model'],
            ]);
            $method = $data['payment_method'];
            $total = (float) $catalog->price;
            $received = $method === 'cash' ? (float) ($data['amount_received'] ?? 0) : $total;

            if ($method === 'cash' && $received < $total) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Cash received must cover the service price.',
                ]);
            }

            $booking = ServiceBooking::create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'service_catalog_id' => $catalog->id,
                'requested_service' => $catalog->name,
                'scheduled_date' => $data['scheduled_date'],
                'status' => 'received',
                'estimated_cost' => $total,
                'received_at' => now(),
            ]);
            $booking->statusHistories()->create([
                'from_status' => null,
                'to_status' => 'received',
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);
            $booking->assignedStaff()->sync($data['assigned_staff']);
            $ledger = $booking->transaction()->create([
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'source_type' => ServiceBooking::class,
                'source_id' => $booking->id,
                'subtotal' => $total,
                'tax' => 0,
                'total' => $total,
                'status' => 'posted',
            ]);
            $ledger->payments()->create([
                'method' => $method,
                'amount' => $total,
                'amount_received' => $received,
                'change_amount' => max(0, $received - $total),
                'proof_of_payment_path' => $method === 'e-wallet' && ! empty($data['proof_of_payment'])
                    ? $data['proof_of_payment']->store('payment-proofs', 'local')
                    : null,
            ]);

            return $booking->load('vehicle', 'serviceCatalog', 'transaction.payments', 'statusHistories');
        });
    }

    /** Advances the supplied booking one status and returns it with system timestamps. */
    public function updateStatus(ServiceBooking $booking, string $status, $user): ServiceBooking
    {
        $allowed = [
            'received',
            'in progress',
            'ready for pickup',
            'released',
        ];
        abort_unless(in_array($status, $allowed, true), 422);
        abort_if($booking->status === $status, 409, 'The vehicle is already at this status.');
        $index = array_search($booking->status, $allowed, true);
        abort_if($index === false || array_search($status, $allowed, true) !== $index + 1, 422, 'Statuses must advance in order.');
        $column = str_replace(' ', '_', $status).'_at';

        if ($status === 'ready for pickup') {
            $column = 'ready_for_pickup_at';
        }

        return DB::transaction(function () use ($booking, $status, $user, $column) {
            $booking->statusHistories()->create([
                'from_status' => $booking->status,
                'to_status' => $status,
                'changed_by' => $user->id,
                'changed_at' => now(),
            ]);
            ServiceBooking::updateTimeline(fn () => $booking->update([
                'status' => $status,
                $column => now(),
            ]));
            app(AuditService::class)->record(
                'autocare.status_changed',
                $booking,
                [
                    'status' => $booking->getOriginal('status'),
                ],
                [
                    'status' => $status,
                ],
            );

            return $booking->fresh();
        });
    }
}
