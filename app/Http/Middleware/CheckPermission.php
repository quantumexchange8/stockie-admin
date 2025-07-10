<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (auth()->check() && !auth()->user()->can($permission)) {
            $allowedRoute = $this->getFirstAllowedRoute();

            if ($allowedRoute) {
                // Redirect to first permitted route or dashboard
                return redirect()->route($allowedRoute);
            }

            throw UnauthorizedException::forPermissions([$permission]);
        }

        return $next($request);
    }

    protected function getFirstAllowedRoute()
    {
        $user = auth()->user();
        
        // Define your route-permission mapping
        $permissionRoutes = [
            'dashboard' => 'dashboard',
            'order-management' => 'orders',
            'shift-control' => 'shift-management.control',
            'shift-record' => 'shift-management.record',
            'menu-management' => 'products',
            'all-report' => 'report',
            'inventory' => 'inventory',
            'waiter' => 'waiter',
            'customer' => 'customer',
            'table-room' => 'table-room',
            'reservation' => 'reservations',
            'transaction-listing' => 'transactions.transaction-listing',
            'einvoice-submission' => 'e-invoice.einvoice-listing',
            'loyalty-programme' => 'loyalty-programme',
            'admin-user' => 'admin-user',
            'sales-analysis' => 'summary.report',
            'activity-logs' => 'activity-logs',
            'configuration' => 'configurations',
        ];
        
        foreach ($permissionRoutes as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }
        
        // Fallback if no permissions match
        return null;
    }
}
