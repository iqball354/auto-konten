<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\PaymentSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ($request->user()->role ?? null) === 'admin' && $request->boolean('reset_cleanup')) {
            PaymentSetting::set('monthly_cleanup_at', '', 'Monthly post cleanup');
        }

        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (($user->role ?? null) === 'admin') {
            if ($user->is_active !== true) {
                $user->forceFill(['is_active' => true])->save();
            }

            return $next($request);
        }

        $subscription = Subscription::where('user_id', $user->id)
            ->latest()
            ->first();

        $shouldActive = false;

        if ($subscription && $subscription->status === 'active') {
            if (!$subscription->expired_at || $subscription->expired_at->isFuture()) {
                $shouldActive = true;
            } else {
                $subscription->update(['status' => 'expired']);
            }
        }

        if ($user->is_active !== $shouldActive) {
            $user->forceFill(['is_active' => $shouldActive])->save();
        }

        return $next($request);
    }
}
