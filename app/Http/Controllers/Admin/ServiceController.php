<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\ModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ModuleService $moduleService
    ) {
    }

    /**
     * Display a listing of services available in CRM.
     */
    public function index(): View
    {
        $modules = $this->moduleService->listAll();
        $canManageActivationActions = auth()->user()?->canManageActivationActions() === true;

        $serviceSnapshot = [
            'total' => (int) $modules->count(),
            'enabled' => (int) $modules->where('is_enabled', true)->count(),
            'disabled' => (int) $modules->where('is_enabled', false)->count(),
        ];

        $moduleRows = $this->buildServiceIndexRows($modules, $canManageActivationActions);

        return view('modules.services.index', compact(
            'moduleRows',
            'serviceSnapshot',
            'canManageActivationActions'
        ));
    }

    /**
     * Toggle module enabled status.
     */
    public function toggle(Module $module): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageActivationActions(), 403);

        if ($module->key === 'service_manager' && $module->is_enabled) {
            return redirect()
                ->route('services.index')
                ->with('error', 'System Modules cannot be disabled.');
        }

        $module->update([
            'is_enabled' => ! $module->is_enabled,
        ]);

        ModuleService::flushCache();

        $status = $module->is_enabled ? 'enabled' : 'disabled';

        return redirect()
            ->route('services.index')
            ->with('success', "Module {$module->name} was successfully {$status}.");
    }

    private function buildServiceIndexRows($modules, bool $canManageActivationActions): array
    {
        return $modules->map(function (Module $module) use ($canManageActivationActions): array {
            $isEnabled = (bool) $module->is_enabled;

            return [
                'module' => $module,
                'is_enabled' => $isEnabled,
                'status_label' => $isEnabled ? 'ENABLED' : 'DISABLED',
                'status_class' => $isEnabled
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
                'card_style' => $isEnabled ? null : 'background-color: #ffcece;',
                'description' => $module->description ?: 'No module description.',
                'toggle_url' => route('services.toggle', $module),
                'toggle_label' => $isEnabled ? 'Disable Module' : 'Enable Module',
                'toggle_button_class' => $isEnabled ? 'btn-secondary-sm' : 'btn-primary-sm',
                'can_toggle' => $canManageActivationActions,
            ];
        })->all();
    }
}
