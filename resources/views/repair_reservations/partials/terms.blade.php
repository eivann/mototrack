{{-- RD2-R5: Terms for same-day service appointment requests. Only confirmed policies are stated.
     Deliberately NOT stated (pending owner approval): automatic cancellation, a capacity number,
     what happens to a request nobody answers, and any refund or fee commitment. --}}
<details {{ ($expanded ?? false) ? 'open' : '' }} class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4 text-sm text-gray-700 dark:text-gray-300">
    <summary class="cursor-pointer font-semibold text-gray-900 dark:text-white">Service appointment terms</summary>
    <ul class="mt-3 space-y-2 list-disc list-inside">
        <li>Appointment requests are for <strong>today only</strong>. Advance and past-time bookings are not accepted.</li>
        <li>You can choose a time from {{ $open }} to {{ $close }} (Philippine time). Times that are already taken or have passed are not offered.</li>
        <li>Sending a request does <strong>not</strong> confirm an appointment. The shop owner reviews each request and may <strong>accept</strong> or <strong>decline</strong> it, for example when the shop is fully booked. You will be notified of the decision.</li>
        <li>Please give accurate details about your motorcycle and the work you need, and arrive at the time the shop accepted.</li>
        <li>You can cancel your own request from Track My Repairs while it is still pending or accepted.</li>
        <li>The final price of any repair is confirmed by the shop. Nothing is charged when you send a request.</li>
    </ul>
</details>
