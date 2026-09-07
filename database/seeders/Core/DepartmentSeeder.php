<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'code'        => 'PROJ',
                'name'        => 'Project Management',
                'description' => 'Divisi manajemen proyek lapangan, perancangan, dan eksekusi teknis (PM, Site Engineer, Admin Project)',
                'status'      => 'active',
            ],
            [
                'code'        => 'PROC',
                'name'        => 'Procurement & Purchasing',
                'description' => 'Divisi pengadaan material, sourcing supplier/vendor, dan penerbitan Purchase Order',
                'status'      => 'active',
            ],
            [
                'code'        => 'GA',
                'name'        => 'General Affair (GA)',
                'description' => 'Divisi operasional umum, utilitas kantor/lapangan, dan fasilitas kerja',
                'status'      => 'active',
            ],
            [
                'code'        => 'LOG',
                'name'        => 'Logistik & Gudang',
                'description' => 'Divisi pengelolaan persediaan gudang, penerimaan barang (GRN), dan distribusi material',
                'status'      => 'active',
            ],
            [
                'code'        => 'FIN',
                'name'        => 'Finance & Accounting',
                'description' => 'Divisi verifikasi tagihan supplier (SID), Payment Advice (PA), arus kas, dan perpajakan',
                'status'      => 'active',
            ],
            [
                'code'        => 'HRD',
                'name'        => 'Human Resource (HRD)',
                'description' => 'Divisi rekrutmen, kepegawaian, absensi, payroll, dan hubungan industrial',
                'status'      => 'active',
            ],
            [
                'code'        => 'IT',
                'name'        => 'Information Technology (IT)',
                'description' => 'Divisi infrastruktur jaringan, hardware, keamanan data, dan pengembangan sistem ERP',
                'status'      => 'active',
            ],
            [
                'code'        => 'HSE',
                'name'        => 'Health, Safety & Environment (HSE)',
                'description' => 'Divisi keselamatan kerja, kesehatan lingkungan, dan standar K3 proyek',
                'status'      => 'active',
            ],
            [
                'code'        => 'EXEC',
                'name'        => 'Executive & Management',
                'description' => 'Dewan direksi, CEO, dan pimpinan manajemen eksekutif perusahaan',
                'status'      => 'active',
            ],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name'        => $dept['name'],
                    'description' => $dept['description'],
                    'status'      => $dept['status'],
                ]
            );
        }
    }
}
