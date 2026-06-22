<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Settings\SettingsGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Admin\BackupService;
use App\Services\Admin\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly BackupService $backupService,
    ) {
    }

    public function index(): View
    {
        Gate::authorize('settings.view');

        $activeTab = SettingsGroup::tryFrom((string) request('tab', 'general')) ?? SettingsGroup::General;

        return view('pages.admin.settings.index', [
            'tabs' => SettingsGroup::tabs(),
            'activeTab' => $activeTab,
            'settings' => $this->settingsService->allForDisplay(),
            'infrastructure' => $this->settingsService->infrastructureStatus(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $group): RedirectResponse
    {
        Gate::authorize('settings.update');

        $settingsGroup = SettingsGroup::from($group);
        $this->settingsService->updateGroup($settingsGroup, $request->validated());

        return redirect()
            ->route('admin.settings.index', ['tab' => $settingsGroup->value])
            ->with('status', $settingsGroup->label().' settings saved successfully.');
    }

    public function runBackup(): RedirectResponse
    {
        Gate::authorize('settings.update');

        $result = $this->backupService->run();

        return redirect()
            ->route('admin.settings.index', ['tab' => SettingsGroup::Backup->value])
            ->with('status', "Backup created ({$result['tables']} tables, {$result['size']} bytes).");
    }
}
