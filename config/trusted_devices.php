<?php

/*
|--------------------------------------------------------------------------
| RD2-R4 — Trusted ("remember this device") logins
|--------------------------------------------------------------------------
| After a successful OTP the user may tick "Remember this device". On later PASSWORD logins from that
| browser the emailed OTP is skipped while the device token is valid. The password is always required;
| unknown / expired / revoked / malformed tokens always get the normal OTP.
|
| PROVISIONAL: 30 days (owner decision pending). The expiry is absolute — using the device never extends it.
*/
return [
    'enabled' => (bool) env('TRUSTED_DEVICE_ENABLED', true),
    'days' => max(1, (int) env('TRUSTED_DEVICE_DAYS', 30)),
    'cookie' => 'mt_trusted_device',
    // Newest N active devices per user are kept; older ones are revoked automatically.
    'max_per_user' => max(1, (int) env('TRUSTED_DEVICE_MAX', 10)),
];
