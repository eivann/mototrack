<x-mail::message>
# Your MotoTrack Verification Code

Please use the following 6-digit One-Time Password (OTP) to verify your login.

<x-mail::panel>
**{{ $otpCode }}**
</x-mail::panel>

This code will expire in 10 minutes. If you did not request this, please ignore this email or secure your account.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
