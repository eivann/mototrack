<?php

namespace App\Http\Controllers\RepairReservation;

use App\Http\Controllers\Controller;
use App\Models\RepairBlackout;
use App\Models\RepairReservation;
use App\Services\RepairBlackoutService;
use App\Services\RepairReservationException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RD2-R3 Phase 4 owner side (route group is auth + owner): manage closed times for repair reservations. */
class OwnerRepairBlackoutController extends Controller
{
    public function __construct(private RepairBlackoutService $blackouts)
    {
    }

    public function index()
    {
        $now = Carbon::now($this->blackouts->tz());
        $tz = config('app.timezone');

        $upcoming = RepairBlackout::query()->where('ends_at', '>', $now->copy()->timezone($tz))->orderBy('starts_at')->get();
        $past = RepairBlackout::query()->where('ends_at', '<=', $now->copy()->timezone($tz))->latest('starts_at')->limit(15)->get();

        // Existing active requests that sit inside an upcoming blackout (flag only; nothing is changed).
        $affected = RepairReservation::query()->whereIn('status', [RepairReservation::PENDING, RepairReservation::ACCEPTED])
            ->where('scheduled_at', '>=', $now->copy()->startOfDay()->timezone($tz))->orderBy('scheduled_at')->get();
        $conflicts = $this->blackouts->conflictsFor($affected);

        return view('repair_reservations.blackouts', [
            'upcoming' => $upcoming,
            'past' => $past,
            'conflicts' => $conflicts,
            'affected' => $affected->filter(fn ($r) => isset($conflicts[$r->id])),
            'today' => $now->format('Y-m-d'),
            'times' => collect(app(\App\Services\RepairReservationService::class)->slotStarts($now))->map(fn (Carbon $s) => [$s->format('H:i'), $s->format('g:i A')]),
        ]);
    }

    public function store(Request $request)
    {
        $today = Carbon::now($this->blackouts->tz())->format('Y-m-d');
        $data = $request->validate([
            'type' => ['required', Rule::in([RepairBlackout::SLOT, RepairBlackout::RANGE, RepairBlackout::DAY])],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today],
            'time' => ['required_if:type,slot', 'nullable', 'date_format:H:i'],
            'start_time' => ['required_if:type,range', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:type,range', 'nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $blackout = $this->blackouts->create($request->user(), $data);
        } catch (RepairReservationException $e) {
            if ($e->httpStatus === 403) {
                abort(403, $e->getMessage());
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('owner.repair-blackouts.index')
            ->with('success', 'Blackout added: '.$blackout->label().'. Existing requests were not changed; any that fall inside it are flagged below.');
    }

    public function destroy(Request $request, RepairBlackout $blackout)
    {
        try {
            $this->blackouts->remove($request->user(), $blackout);
        } catch (RepairReservationException $e) {
            if ($e->httpStatus === 403) {
                abort(403, $e->getMessage());
            }

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Blackout removed.');
    }
}
