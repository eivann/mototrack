{{-- RD2-R6: the single source of the automatic-cancellation wording (shown only while RR_AUTO_CANCEL_ENABLED is true).
     It describes exactly what RepairReservationService does: an ACCEPTED appointment whose STORED check-in deadline
     (start time minus RR_CHECKIN_MINUTES) has passed without a recorded check-in is cancelled by the scheduler.
     Check-in AT the deadline still counts; the shop owner records the arrival. The number comes from the same
     configuration value that computes the stored deadline, so the text can never drift from the behaviour. --}}
@php
    $acMinutes = max(0, (int) config('repair_reservations.check_in.minutes_before', 30));
    $acExample = \Carbon\Carbon::createFromTime(14, 0)->subMinutes($acMinutes)->format('g:i A');
@endphp
@if ($acMinutes > 0)
    Please arrive and check in with the shop at least <strong>{{ $acMinutes }} minutes</strong> before your appointment time. If the shop has not recorded your arrival by then, your appointment is <strong>cancelled automatically</strong> and you and the shop are notified. Example: for a 2:00 PM appointment, arrive by {{ $acExample }}.
@else
    Please arrive and check in with the shop by your appointment time. If the shop has not recorded your arrival by then, your appointment is <strong>cancelled automatically</strong> and you and the shop are notified.
@endif
