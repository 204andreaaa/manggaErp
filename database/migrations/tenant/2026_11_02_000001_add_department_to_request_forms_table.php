<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Non-project RFs are charged to a department (master `departments`, so no FK across
 * databases). The name is kept too, so the RF still reads right after a rename or delete.
 * Existing non-project RFs take their creator's department when it matches the master list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('project_code')->index();
            $table->string('department_name', 100)->nullable()->after('department_id');
        });

        $rfs = DB::table('request_forms')->where('record_type', 'non_project')->whereNotNull('created_by_id')->get(['id', 'created_by_id']);
        if ($rfs->isEmpty()) {
            return;
        }

        $master = DB::connection('master');
        $deptByUser = $master->table('employees')
            ->join('departments', 'departments.name', '=', 'employees.department')
            ->whereIn('employees.user_id', $rfs->pluck('created_by_id')->unique())
            ->get(['employees.user_id', 'departments.id', 'departments.name'])
            ->keyBy('user_id');

        foreach ($rfs as $rf) {
            if ($dept = $deptByUser[$rf->created_by_id] ?? null) {
                DB::table('request_forms')->where('id', $rf->id)->update(['department_id' => $dept->id, 'department_name' => $dept->name]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('request_forms', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
            $table->dropColumn(['department_id', 'department_name']);
        });
    }
};
