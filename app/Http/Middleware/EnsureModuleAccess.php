<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Commerce\IeltsModule;
use App\Services\Enrollment\PackageAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function __construct(private readonly PackageAccessService $access)
    {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $ieltsModule = IeltsModule::from($module);

        if (! $this->access->canAccessModule($user, $ieltsModule)) {
            abort(403, 'You do not have access to this module or your attempt limit has been reached.');
        }

        return $next($request);
    }
}
