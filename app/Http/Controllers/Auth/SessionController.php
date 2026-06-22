<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private readonly DeviceController $devices)
    {
    }

    public function index(Request $request): View
    {
        return $this->devices->index($request);
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        return $this->devices->destroyOthers($request);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        return $this->devices->destroySession($request, $session);
    }
}
