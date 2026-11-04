<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\TrustedDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/** RD2-R4: review and revoke remembered devices. Own devices only; revoking needs the current password. */
class TrustedDeviceController extends Controller
{
    public function __construct(private TrustedDeviceService $devices)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $token = $request->cookie($this->devices->cookieName());
        $current = $this->devices->find($user, is_string($token) ? $token : null);

        return view('profile.trusted-devices', [
            'devices' => $user->trustedDevices()->active()->orderByDesc('last_used_at')->get(),
            'currentId' => $current?->id,
            'days' => $this->devices->days(),
            'enabled' => $this->devices->enabled(),
        ]);
    }

    public function destroy(Request $request, int $device)
    {
        $request->validateWithBag('trustedDevices', ['current_password' => ['required', 'current_password']]);

        // Scoped to the signed-in user: someone else's device id is simply "not found".
        $row = $request->user()->trustedDevices()->whereKey($device)->firstOrFail();
        $token = $request->cookie($this->devices->cookieName());
        $wasCurrent = $this->devices->find($request->user(), is_string($token) ? $token : null)?->id === $row->id;

        $this->devices->revoke($row);
        if ($wasCurrent) {
            Cookie::queue($this->devices->forgetCookie($request));
        }

        return redirect()->route('profile.devices')->with('status', 'device-revoked');
    }

    public function destroyAll(Request $request)
    {
        $request->validateWithBag('trustedDevices', ['current_password' => ['required', 'current_password']]);

        $this->devices->revokeAll($request->user());
        Cookie::queue($this->devices->forgetCookie($request));

        return redirect()->route('profile.devices')->with('status', 'devices-revoked');
    }
}
