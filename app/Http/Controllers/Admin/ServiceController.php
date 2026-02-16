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

        return view('admin.services.index', compact('modules'));
    }

    /**
     * Toggle module enabled status.
     */
    public function toggle(Module $module): RedirectResponse
    {
        $module->update([
            'is_enabled' => ! $module->is_enabled,
        ]);

        $status = $module->is_enabled ? 'enabled' : 'disabled';

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Module {$module->name} was successfully {$status}.");
    }
}
