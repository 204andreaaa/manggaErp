<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\User;
use App\Models\Role;

class ApprovalConfigController extends Controller
{
    private const SINGLE_STEP_TYPES = ['po_verification', 'gr_verification'];
    private const MULTI_LEVEL_TYPES = ['request_form', 'purchase_order', 'payment_advice'];

    public function index()
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $configs = ErpApprovalConfig::with(['role', 'user'])->orderBy('level')->get();

        $rfConfigs = $configs->where('record_type', 'request_form');
        $poConfigs = $configs->where('record_type', 'purchase_order');
        $paConfigs = $configs->where('record_type', 'payment_advice');
        $poVerifConfigs = $configs->where('record_type', 'po_verification');
        $grVerifConfigs = $configs->where('record_type', 'gr_verification');

        $projectId = session('project_id') ?? session('current_project');
        $usersQuery = User::orderBy('name');
        if ($projectId) {
            $usersQuery->whereHas('projects', function ($q) use ($projectId) {
                $q->where('projects.id', $projectId);
            });
        }
        $users = $usersQuery->get();
        if ($users->isEmpty()) {
            $users = User::orderBy('name')->get();
        }

        $roles = Role::orderBy('name')->get();

        $maxLevels = [
            'request_form' => $rfConfigs->max('level') ?? 0,
            'purchase_order' => $poConfigs->max('level') ?? 0,
            'payment_advice' => $paConfigs->max('level') ?? 0,
            'po_verification' => $poVerifConfigs->max('level') ?? 0,
            'gr_verification' => $grVerifConfigs->max('level') ?? 0,
        ];

        $coverageGaps = $this->findCoverageGaps($configs);

        return view('erp.approval_configs.index', compact(
            'rfConfigs', 'poConfigs', 'paConfigs', 'poVerifConfigs', 'grVerifConfigs',
            'users', 'roles', 'maxLevels', 'coverageGaps'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $data = $this->validateConfigData($request);
        $this->assertNoConflict($data);

        ErpApprovalConfig::create($data);

        return back()->with('success', 'Konfigurasi Approval berhasil ditambahkan.');
    }

    public function update(Request $request, ErpApprovalConfig $approval_config)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $data = $this->validateConfigData($request);
        $this->assertNoConflict($data, $approval_config->id);

        $approval_config->update($data);

        return back()->with('success', 'Konfigurasi Approval berhasil diperbarui.');
    }

    public function destroy(ErpApprovalConfig $approval_config)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $approval_config->delete();

        return back()->with('success', 'Konfigurasi Approval berhasil dihapus.');
    }

    /**
     * Shared validation for store() and update(). Assignment can be to a specific
     * user OR a role (never both) — role_id support was already wired into the
     * runtime authorization checks but had no way to be submitted from the UI.
     */
    private function validateConfigData(Request $request): array
    {
        // Blank number inputs are normally omitted from the POST body by the browser, but
        // normalize defensively in case a client sends them as empty strings instead —
        // otherwise they'd hit the decimal columns as '' and fail at the DB level.
        foreach (['min_amount', 'max_amount'] as $field) {
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        $data = $request->validate([
            'record_type' => 'required|in:request_form,purchase_order,payment_advice,po_verification,gr_verification',
            'level' => 'required|integer|min:1',
            'name' => 'required|string|max:100',
            'assign_type' => 'required|in:user,role',
            'user_id' => 'required_if:assign_type,user|nullable|exists:master.users,id',
            'role_id' => 'required_if:assign_type,role|nullable|exists:master.roles,id',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'is_project' => 'nullable|in:1,0,',
        ]);

        if (in_array($data['record_type'], self::SINGLE_STEP_TYPES, true)) {
            $data['level'] = 1;
            $data['min_amount'] = null;
            $data['max_amount'] = null;
            $data['is_project'] = null;
        } else {
            $data['is_project'] = ($data['is_project'] ?? '') === '' ? null : $data['is_project'];
        }

        if ($data['assign_type'] === 'role') {
            $data['user_id'] = null;
        } else {
            $data['role_id'] = null;
        }
        unset($data['assign_type']);

        return $data;
    }

    /**
     * Prevent two configs from silently competing for the same approval step.
     * Without this, submit() would create two ErpApproval rows for the same
     * level and both approvers would see themselves as "the" approver for it.
     */
    private function assertNoConflict(array $data, ?int $excludeId = null): void
    {
        $recordType = $data['record_type'];

        if (in_array($recordType, self::SINGLE_STEP_TYPES, true)) {
            $exists = ErpApprovalConfig::where('record_type', $recordType)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'record_type' => 'Verifikator untuk tipe ini sudah ada. Edit konfigurasi yang sudah ada, atau hapus dulu sebelum menambah yang baru.',
                ]);
            }

            return;
        }

        $newMin = $data['min_amount'] !== null ? (float) $data['min_amount'] : 0.0;
        $newMax = $data['max_amount'] !== null ? (float) $data['max_amount'] : INF;
        $newIsProject = $data['is_project'];

        $candidates = ErpApprovalConfig::where('record_type', $recordType)
            ->where('level', $data['level'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->get();

        foreach ($candidates as $cfg) {
            $projectOverlap = is_null($cfg->is_project) || is_null($newIsProject)
                || (int) $cfg->is_project === (int) $newIsProject;

            if (!$projectOverlap) {
                continue;
            }

            $existMin = $cfg->min_amount !== null ? (float) $cfg->min_amount : 0.0;
            $existMax = $cfg->max_amount !== null ? (float) $cfg->max_amount : INF;

            if ($newMin <= $existMax && $existMin <= $newMax) {
                throw ValidationException::withMessages([
                    'min_amount' => "Rentang nominal ini tumpang tindih dengan konfigurasi \"{$cfg->name}\" (Level {$cfg->level}) yang sudah ada untuk kondisi project type yang sama.",
                ]);
            }
        }
    }

    /**
     * Informational only (does not block saving): flags amount ranges that no
     * config currently covers, so gaps show up when an admin looks at the page
     * instead of only when a real document fails to submit.
     */
    private function findCoverageGaps($configs): array
    {
        $gaps = [];

        foreach (self::MULTI_LEVEL_TYPES as $type) {
            $level1Configs = $configs->where('record_type', $type)->where('level', 1);

            if ($level1Configs->isEmpty()) {
                continue;
            }

            // A real RF/PO is always concretely project (1) or non-project (0) at submit
            // time — is_project=null on a config is only ever a wildcard match. Payment
            // Advice submission never filters by is_project at all, so it only needs one pass.
            $isProjectVariants = $type === 'payment_advice' ? [null] : [1, 0];

            foreach ($isProjectVariants as $isProject) {
                $relevant = $level1Configs->filter(function ($cfg) use ($isProject) {
                    return is_null($isProject) || is_null($cfg->is_project) || (int) $cfg->is_project === (int) $isProject;
                })->sortBy(function ($cfg) {
                    return $cfg->min_amount !== null ? (float) $cfg->min_amount : 0.0;
                });

                if ($relevant->isEmpty()) {
                    continue;
                }

                $cursor = 0.0;
                foreach ($relevant as $cfg) {
                    $min = $cfg->min_amount !== null ? (float) $cfg->min_amount : 0.0;
                    $max = $cfg->max_amount !== null ? (float) $cfg->max_amount : INF;

                    if ($min > $cursor) {
                        $gaps[] = [
                            'record_type' => $type,
                            'is_project' => $isProject,
                            'from' => $cursor,
                            'to' => $min,
                        ];
                    }

                    $cursor = max($cursor, $max);
                }

                if ($cursor !== INF) {
                    $gaps[] = [
                        'record_type' => $type,
                        'is_project' => $isProject,
                        'from' => $cursor,
                        'to' => null,
                    ];
                }
            }
        }

        return $gaps;
    }
}
