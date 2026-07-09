<?php

namespace App\Http\Middleware;

use App\Services\PaymentService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit #11: verify the RCPAY PLUS webhook signature at the route level
 * instead of inside the controller, so the check is enforced structurally
 * and cannot be forgotten if the controller is refactored.
 */
class VerifyRcpaySignature
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->paymentService->verifyRcpayWebhook($request->all())) {
            Log::warning('RCPAY PLUS Webhook: Invalid signature received.');
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        return $next($request);
    }
}
