<?php

namespace App\Http\Concerns;

use Illuminate\Http\RedirectResponse;
use Throwable;

trait HandlesExceptions
{
    /**
     * Execute a callable inside a try/catch and redirect back with a
     * user-friendly error flash on failure.
     *
     * @param  callable(): RedirectResponse  $action
     * @param  string                         $errorMessage  Shown to user in production.
     */
    protected function attempt(callable $action, string $errorMessage = 'Something went wrong. Please try again.'): RedirectResponse
    {
        try {
            return $action();
        } catch (Throwable $e) {
            report($e);

            $message = app()->hasDebugModeEnabled()
                ? $e->getMessage()
                : $errorMessage;

            return back()->with('error', $message);
        }
    }
}
