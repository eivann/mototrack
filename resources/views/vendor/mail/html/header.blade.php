@props(['url'])
{{-- MotoTrack branding for the HTML part of every system e-mail (OTP codes, notifications), whatever
     the configured app name (a default "Laravel" name is shown as MotoTrack, never as the Laravel logo).
     asset() returns an absolute URL from the application's URL configuration (APP_URL for queued/console
     mail; the request host for mail sent during a request, https in production via URL::forceScheme),
     so mail clients can fetch the logo. The alt text still names the sender when images are blocked,
     and the plain-text part keeps Laravel's default text header. --}}
@php
    $brandName = trim(strip_tags((string) $slot));
    if ($brandName === '' || strcasecmp($brandName, 'Laravel') === 0) {
        $brandName = 'MotoTrack';
    }
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('brand/mototrack-em.png') }}" width="56" height="56" alt="{{ $brandName }} logo" style="display: block; width: 56px; height: 56px; max-height: 56px; margin: 0 auto 8px; border: 0; border-radius: 12px;">
<span style="display: block;">{{ $brandName }}</span>
</a>
</td>
</tr>
