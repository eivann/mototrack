<?php

namespace App\Http\Controllers\Repair;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HandlesExceptions;
use App\Http\Requests\Repair\StoreRepairRequest;
use App\Http\Requests\Repair\UpdateRepairRequest;
use Illuminate\Http\Request;
use App\Models\Repair;
use App\Services\RepairCapacityException;
use App\Services\RepairCapacityService;
use App\Services\RepairReservationService;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RepairController extends Controller
{
    use HandlesExceptions;
    public function index(Request $request)
    {
        $query = Repair::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->paid !== null) {
            $query->where('is_paid', $request->paid);
        }

        $repairs = $query->with('customer')->latest()->paginate(10)->withQueryString();
        $customers = \App\Models\Customer::select('id', 'name')->orderBy('name')->get();

        $overCapacity = app(RepairCapacityService::class)->overCapacity();
        $walkInMinutes = app(RepairCapacityService::class)->walkInMinutes();

        return view('repairs.index', compact('repairs', 'customers', 'overCapacity', 'walkInMinutes'));
    }

    public function store(StoreRepairRequest $request)
    {
        $customerName = $request->customer_name;
        if ($request->customer_id) {
            $customerName = \App\Models\Customer::find($request->customer_id)->name;
        }

        Repair::create([
            'customer_id'   => $request->customer_id,
            'customer_name' => ucwords($customerName),
            'bike_model'    => ucwords($request->bike_model),
            'issue'         => ucfirst($request->issue),
            'cost'          => $request->cost,
            'status'        => 'pending',
            'is_paid'       => false,
        ]);

        return back()->with('success', 'Repair job created successfully!');
    }

    public function update(UpdateRepairRequest $request, Repair $repair)
    {
        // RD2-R3 Phase 2/3: every status change goes through the capacity service (per-day lock, interval
        // overlap, linked-reservation rules): Start checks capacity, Done frees the bay and completes a linked
        // reservation in the same transaction, Pending releases the bay (refused for a reservation's repair).
        $capacity = app(RepairCapacityService::class);
        try {
            $repair = match ($request->status) {
                'ongoing' => $capacity->startWalkIn($repair, $request->integer('estimated_minutes') ?: null),
                'done', 'completed' => $capacity->finishWalkIn($repair, $request->status),
                default => $capacity->releaseWalkIn($repair),
            };
        } catch (RepairCapacityException $e) {
            return redirect('/repairs')->with('error', $e->getMessage());
        }

        if ($repair->relationLoaded('justCompletedReservation')) {
            app(RepairReservationService::class)->notifyCompleted($repair->getRelation('justCompletedReservation'));
        }

        // A repair started from an online reservation notifies through the reservation ("Repair started" /
        // "Service completed"), so the generic repair notice is skipped to avoid a duplicate, misleading message.
        if (! $repair->reservation_id && $repair->customer && $repair->customer->user) {
            $repair->customer->user->notify(new \App\Notifications\StatusUpdated(
                'Repair Status Updated',
                "Your repair job for {$repair->bike_model} is now: " . strtoupper($request->status),
                route('customer.dashboard', ['tab' => 'repairs']) . '#repair-' . $repair->id
            ));
        }

        return redirect('/repairs')->with('success', 'Status updated!');
    }

    /** Owner: add time to a repair in progress (also resolves an overdue or legacy ongoing record without deleting it). */
    public function extend(Request $request, Repair $repair)
    {
        Gate::authorize('update', $repair);
        $data = $request->validate(['extra_minutes' => 'required|integer|min:5|max:480']);

        try {
            $result = app(RepairCapacityService::class)->extend($repair, (int) $data['extra_minutes']);
        } catch (RepairCapacityException $e) {
            return redirect('/repairs')->with('error', $e->getMessage());
        }

        return redirect('/repairs')->with($result['warning'] ? 'error' : 'success', $result['warning'] ?? 'Estimate extended.');
    }

    /** Owner: enter or correct the actual cost of an UNPAID repair (never auto-priced, never marks it paid). */
    public function setCost(Request $request, Repair $repair)
    {
        Gate::authorize('update', $repair);
        $data = $request->validate(['cost' => 'required|numeric|min:0|max:9999999.99']);

        return DB::transaction(function () use ($repair, $data) {
            $locked = Repair::lockForUpdate()->findOrFail($repair->id);
            if ($locked->is_paid) {
                return back()->with('error', 'This repair is already paid, so its cost can no longer be changed.');
            }
            $locked->cost = $data['cost'];
            $locked->save();

            return back()->with('success', 'Repair cost saved. You can now collect payment.');
        });
    }

    public function destroy(Repair $repair)
    {
        Gate::authorize('delete', $repair);

        $repair->delete();
        return back()->with('success', 'Repair job deleted.');
    }

    public function markPaid(Repair $repair, Request $request)
    {
        Gate::authorize('markPaid', $repair);

        return $this->attempt(function () use ($repair, $request) {
            if ($repair->cost === null) {
                return back()->with('error', 'Enter the repair cost before collecting payment.');
            }

            $alreadyPaid = false;
            $tendered = (float) $request->input('amount_tendered');

            DB::transaction(function () use ($repair, $tendered, &$alreadyPaid) {
                $lockedRepair = Repair::lockForUpdate()->find($repair->id);
                
                if ($lockedRepair->is_paid) {
                    $alreadyPaid = true;
                    return;
                }

                if ($lockedRepair->cost === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount_tendered' => 'Enter the repair cost before collecting payment.',
                    ]);
                }

                if ($tendered < (float) $lockedRepair->cost) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount_tendered' => 'Full cash payment is required.',
                    ]);
                }

                $lockedRepair->is_paid = true;
                $lockedRepair->save();

                // SYS4-06 D2B: header + sale in the same transaction as the is_paid change.
                app(\App\Actions\RecordHeaderedSale::class)->execute(
                    \App\Models\SaleHeader::TYPE_REPAIR, 'REPAIR-',
                    [
                        'inventory_item_id' => null,
                        'quantity' => 1,
                        'price' => $lockedRepair->cost,
                        'total_price' => $lockedRepair->cost,
                        'customer_id' => $lockedRepair->customer_id,
                    ],
                    $tendered
                );
            });

            if ($alreadyPaid) {
                return back()->with('error', 'This repair has already been paid.');
            }

            $owners = User::owners();
            Notification::send($owners, new StatusUpdated(
                'Repair Payment Received',
                "Payment of ₱" . number_format($repair->cost, 2) . " received for {$repair->bike_model} ({$repair->customer_name}).",
                route('repairs.index') . '#repair-' . $repair->id
            ));

            if ($repair->customer && $repair->customer->user) {
                $repair->customer->user->notify(new StatusUpdated(
                    'Payment Confirmed',
                    "Your payment of ₱" . number_format($repair->cost, 2) . " for {$repair->bike_model} has been received. Thank you!",
                    route('customer.dashboard', ['tab' => 'repairs']) . '#repair-' . $repair->id
                ));
            }

            return back()->with('success', 'Payment recorded and added to sales history!');
        }, 'Payment recording failed. Please try again.');
    }

    public function receipt(Repair $repair)
    {
        return view('repairs.receipt', compact('repair'));
    }
}
