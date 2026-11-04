<?php

namespace App\Http\Controllers\RepairReservation;

use App\Http\Controllers\Controller;
use App\Models\RepairReservation;
use App\Services\RepairBlackoutService;
use App\Services\RepairCapacityException;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Illuminate\Http\Request;

/** RD2-R3 owner side (route group is auth + owner): decide customer repair/service requests. */
class OwnerRepairReservationController extends Controller
{
    public function __construct(private RepairReservationService $reservations)
    {
    }

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['pending', 'accepted', 'history'], true) ? $request->query('tab') : 'pending';

        $query = RepairReservation::query()->with('customer');
        match ($tab) {
            'accepted' => $query->where('status', RepairReservation::ACCEPTED)->with('repair')->orderBy('scheduled_at'),
            'history' => $query->whereIn('status', [RepairReservation::REJECTED, RepairReservation::CANCELLED, RepairReservation::EXPIRED, RepairReservation::COMPLETED])->latest('id'),
            default => $query->where('status', RepairReservation::PENDING)->orderBy('scheduled_at'),
        };

        $today = $this->reservations->now()->format('Y-m-d');

        $items = $query->paginate(15)->withQueryString();

        return view('repair_reservations.owner_index', [
            'items' => $items,
            'tab' => $tab,
            'counts' => [
                'pending' => RepairReservation::where('status', RepairReservation::PENDING)->count(),
                'accepted' => RepairReservation::where('status', RepairReservation::ACCEPTED)->where('service_date', $today)->count(),
            ],
            'capacity' => $this->reservations->capacity(),
            'unit' => str_replace('_', ' ', $this->reservations->capacityUnit()),
            'now' => $this->reservations->now(),
            'blackoutConflicts' => in_array($tab, ['pending', 'accepted'], true) ? app(RepairBlackoutService::class)->conflictsFor($items) : [],
            'conflicts' => $tab === 'accepted' ? app(RepairCapacityService::class)->reservationConflicts($items) : [],
        ]);
    }

    public function accept(Request $request, RepairReservation $reservation)
    {
        return $this->run(fn () => $this->reservations->accept($reservation, $request->user()), 'Appointment accepted. The customer has been notified.');
    }

    public function reject(Request $request, RepairReservation $reservation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], ['reason.required' => 'Give the customer a short reason for rejecting.']);

        return $this->run(fn () => $this->reservations->reject($reservation, $request->user(), $data['reason']), 'Request rejected. The customer has been notified.');
    }

    public function cancel(Request $request, RepairReservation $reservation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], ['reason.required' => 'Give the customer a short reason for cancelling.']);

        return $this->run(fn () => $this->reservations->cancel($reservation, $request->user(), $data['reason']), 'Appointment cancelled. The customer has been notified.');
    }

    public function checkIn(Request $request, RepairReservation $reservation)
    {
        $data = $request->validate(['received_by' => ['nullable', 'string', 'max:80']]);

        return $this->run(fn () => $this->reservations->checkIn($reservation, $request->user(), $data['received_by'] ?? null), 'Customer checked in.');
    }

    public function startRepair(Request $request, RepairReservation $reservation)
    {
        $data = $request->validate(['estimated_minutes' => ['nullable', 'integer', 'min:5', 'max:1440']]);

        return $this->run(function () use ($request, $reservation, $data) {
            $result = app(RepairCapacityService::class)->startReservationRepair($reservation, $request->user(), $data['estimated_minutes'] ?? null);
            if ($result['changed']) {
                $this->reservations->notifyRepairStarted($reservation->fresh('customer'));
            }

            return ['changed' => $result['changed']];
        }, 'Repair started. It is now in the Repairs list.');
    }

    private function run(\Closure $action, string $ok)
    {
        try {
            $result = $action();
        } catch (RepairCapacityException $e) {
            return back()->with('error', $e->getMessage());
        } catch (RepairReservationException $e) {
            if ($e->httpStatus === 403) {
                abort(403, $e->getMessage());
            }

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $result['changed'] ? $ok : 'No change: this request was already in that state.');
    }
}
