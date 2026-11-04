<?php

/*
|--------------------------------------------------------------------------
| RD2-R3 — Customer repair / service appointment requests
|--------------------------------------------------------------------------
| Business timezone Asia/Manila. Same-day requests only, inside the shop window.
|
| CONFIRMED schedule (R3 Revision 4): first start 07:30, last start 17:00 inclusive, every 30 minutes
| (20 possible starts). Check-in is due 30 minutes before the start; owner acceptance and new customer
| requests must land strictly before the approval deadline (= check-in deadline minus the approval buffer).
|
| PROVISIONAL / UNCONFIRMED (owner decisions pending — do not present as final policy):
|  - the exact duration choices and the operating/closing time (5:00 PM is the planned LAST START only);
|  - the approval buffer value (ships as 0);
|  - the capacity UNIT (per_day | per_slot | simultaneous) — true-simultaneous counting arrives in a later phase;
|  - the auto-cancel and pending-expiry eligibility rules (both stay OFF).
| Only ACCEPTED requests consume capacity; a pending request is never a guaranteed appointment.
*/
return [
    'timezone' => 'Asia/Manila',

    'open' => env('RR_OPEN', '07:30'),              // first appointment start
    'last_start' => env('RR_LAST_START', '17:00'),  // last appointment start, INCLUSIVE
    'slot_minutes' => (int) env('RR_SLOT_MINUTES', 30),

    // Provisional: 3 (shop reported 3–5). Clamped to 1–5 at read time; never hard-coded in logic.
    'capacity' => (int) env('RR_CAPACITY', 3),
    // per_day: accepted requests per service date | per_slot: per start time |
    // simultaneous: accepted requests whose [start, start+duration) windows overlap.
    'capacity_unit' => env('RR_CAPACITY_UNIT', 'simultaneous'),
    'appointment_minutes' => (int) env('RR_APPOINTMENT_MINUTES', 60), // used by "simultaneous" only
    // Assumed duration of a walk-in repair when the owner gives no estimate (Phase 2, PROVISIONAL).
    'walk_in_minutes' => (int) env('RR_WALKIN_MINUTES', 60),

    // Check-in is due this many minutes before the start. Check-in at exactly the deadline is valid.
    'check_in' => [
        'minutes_before' => (int) env('RR_CHECKIN_MINUTES', 30),
    ],

    // Owner acceptance and new requests must happen strictly before (check-in deadline − buffer).
    // The buffer is PROVISIONAL and ships as 0 (so the approval and check-in deadlines coincide).
    'approval' => [
        'buffer_minutes' => (int) env('RR_APPROVAL_BUFFER_MINUTES', 0),
    ],

    'service_types' => [
        'general_service' => 'General service / tune-up',
        'oil_change' => 'Oil change',
        'brakes' => 'Brakes',
        'tires' => 'Tires / wheels',
        'electrical' => 'Electrical / battery',
        'engine' => 'Engine / transmission',
        'other' => 'Other',
    ],

    /*
    | Automatic NO-CHECK-IN cancellation (Phase 5). Applies ONLY to an ACCEPTED reservation with no check-in and no
    | linked repair, once its STORED check-in deadline is strictly in the past. OFF by default and permanently off
    | until the owner approves go-live; no console option can bypass this flag.
    */
    'auto_cancel' => [
        'enabled' => (bool) env('RR_AUTO_CANCEL_ENABLED', false),
    ],

    // Upper bound of reservations one scheduler/console run will change (Phase 5).
    'batch_size' => 100,

    /*
    | Automatic expiry of unanswered PENDING requests once their STORED approval deadline is reached (a distinct
    | `expired` status, separate from auto-cancel). OFF by default and permanently off until the owner approves
    | go-live; no console option can bypass this flag.
    */
    'pending_expiry' => [
        'enabled' => (bool) env('RR_PENDING_EXPIRY_ENABLED', false),
    ],
];
