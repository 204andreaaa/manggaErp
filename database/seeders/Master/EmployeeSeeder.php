<?php

namespace Database\Seeders\Master;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Otomatis sinkronisasi seluruh data user aktif ke tabel profil employees
     */
    public function run(): void
    {
        $users = User::on('master')->with('roles')->get();

        DB::connection('master')->beginTransaction();
        try {
            $seq = 1;
            foreach ($users as $u) {
                $nik = sprintf('EMP-%04d', $u->id);

                // Tentukan perkiraan departemen berdasarkan role
                $dept = 'Umum';
                if ($u->hasRole(['admin_project', 'project_manager', 'site_engineer'])) {
                    $dept = 'Project Management';
                } elseif ($u->hasRole(['procurement', 'general_affair'])) {
                    $dept = 'Procurement & GA';
                } elseif ($u->hasRole(['logistik', 'warehouse'])) {
                    $dept = 'Logistik & Gudang';
                } elseif ($u->hasRole(['finance', 'accounting'])) {
                    $dept = 'Finance & Accounting';
                } elseif ($u->hasRole(['ceo', 'superadmin', 'admin'])) {
                    $dept = 'Executive & Management';
                }

                Employee::on('master')->updateOrCreate(
                    ['user_id' => $u->id],
                    [
                        'nik'               => $nik,
                        'name'              => $u->name,
                        'email'             => $u->email,
                        'phone'             => $u->phone,
                        'position'          => $u->position ?? 'Staff',
                        'department'        => $dept,
                        'signature_path'    => $u->signature_path,
                        'employment_status' => 'permanent',
                        'status'            => $u->status ?? 'active',
                        'join_date'         => now()->subMonths(6)->toDateString(),
                    ]
                );
                $seq++;
            }

            DB::connection('master')->commit();
            if ($this->command) {
                $this->command->info("Berhasil sinkronisasi " . count($users) . " data karyawan ke tabel employees!");
            }
        } catch (\Exception $e) {
            DB::connection('master')->rollBack();
            if ($this->command) {
                $this->command->error("Error seeding employees: " . $e->getMessage());
            }
        }
    }
}
