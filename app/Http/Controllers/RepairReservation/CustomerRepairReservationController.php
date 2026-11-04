<?php

namespace App\Http\Controllers\RepairReservation;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RepairReservation;
use App\Services\RepairReservationException;
use App\Services\RepairReservationService;
use Illuminate\Http\Request;

/** RD2-R3 customer side (route group is `auth`): request a same-day repair/service slot, view and cancel it. */
class CustomerRepairReservationController extends Controller
{
    public function __construct(private RepairReservationService $reservations)
    {
    }

    private function customer(): Customer
    {
        $user = auth()->user();

        return Customer::firstOrCreate(['user_id' => $user->id], ['name' => $user->name, 'email' => $user->email]);
    }

    private function owned(string $reference): RepairReservation
    {
        // Another customer's reference is indistinguishable from a missing one.
        return RepairReservation::query()->where('reference', $reference)->where('customer_id', $this->customer()->id)->firstOrFail();
    }

    public function create()
    {
        abort_if(auth()->user()->role === 'owner', 403);
        $customer = $this->customer();
        $now = $this->reservations->now();

        return view('repair_reservations.create', [
            'slots' => $this->reservations->availableSlots(),
            'today' => $now->format('Y-m-d'),
            'todayLabel' => $now->format('l, F j, Y'),
            'open' => \Carbon\Carbon::createFromFormat('H:i', config('repair_reservations.open'))->format('g:i A'),
            'close' => \Carbon\Carbon::createFromFormat('H:i', config('repair_reservations.last_start'))->format('g:i A'),
            'serviceTypes' => config('repair_reservations.service_types'),
            'existing' => RepairReservation::where('customer_id', $customer->id)->whereIn('status', RepairReservation::ACTIVE)
                ->where('service_date', $now->format('Y-m-d'))->first(),
        ]);
    }

    public function store(Request $request)
    {
        abort_if($request->user()->role === 'owner', 403);

        $data = $request->validate([
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'service_type' => ['required', 'string', 'max:40'],
            'bike_model' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'accept_terms' => ['accepted'],
        ], [
            'description.min' => 'Please describe the problem or service in at least 10 characters.',
            'accept_terms.accepted' => 'Please confirm that you understand this is a request subject to owner approval.',
        ]);

        try {
            $reservation = $this->reservations->create($this->customer(), $data);
        } catch (RepairReservationException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('repair-reservations.show', $reservation->reference)
            ->with('success', 'Request sent. It is not confirmed until the shop owner accepts it.');
    }

    public function show(string $reference)
    {
        $reservation = $this->owned($reference)->load('events');

        return view('repair_reservations.show', ['reservation' => $reservation]);
    }

    public function cancel(Request $request, string $reference)
    {
        $reservation = $this->owned($reference);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $result = $this->reservations->cancel($reservation, $request->user(), $data['reason'] ?? null);
        } catch (RepairReservationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('repair-reservations.show', $reservation->reference)
            ->with('success', $result['changed'] ? 'Your request was cancelled.' : 'This request was already cancelled.');
    }
}
