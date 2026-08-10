<?php
 
namespace Database\Seeders;
 
use App\Models\User;
use App\Models\Role;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
 
class MasterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Import seluruh 81+ data pegawai/user master dari CSV ke database Master.
     */
    public function run(): void
    {
        $path = database_path('seeders/csv/users_master.csv');
 
        if (!file_exists($path)) {
            if ($this->command) {
                $this->command->error("Gagal: File CSV tidak ditemukan di $path");
            }
            return;
        }
 
        $file = fopen($path, 'r');
        $header = fgetcsv($file);
 
        if (!$header) {
            if ($this->command) {
                $this->command->error("Gagal: File CSV kosong atau format salah.");
            }
            return;
        }

        $defaultProject = Project::first();
 
        DB::beginTransaction();
        try {
            $count = 0;
            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($header, $row);
 
                $warehouseId = null;
                if (!empty($data['warehouse_id'])) {
                    $warehouseId = $data['warehouse_id'];
                }
 
                $user = User::updateOrCreate(
                    ['username' => $data['username']],
                    [
                        'name'           => $data['name'],
                        'email'          => $data['email'],
                        'phone'          => !empty($data['phone']) ? $data['phone'] : null,
                        'position'       => !empty($data['position']) ? $data['position'] : null,
                        'signature_path' => !empty($data['signature_path']) ? $data['signature_path'] : null,
                        'password'       => $data['password'],
                        'warehouse_id'   => $warehouseId,
                        'status'         => $data['status'] ?? 'active',
                    ]
                );
 
                if ($defaultProject) {
                    $user->projects()->syncWithoutDetaching([$defaultProject->id]);
                }

                if (!empty($data['role_slugs'])) {
                    $slugs = explode(',', $data['role_slugs']);
                    $roleIds = Role::whereIn('slug', $slugs)->pluck('id')->all();
                    if (!empty($roleIds)) {
                        $user->roles()->sync($roleIds);
                    }
                }
 
                $count++;
            }
 
            DB::commit();
            if ($this->command) {
                $this->command->info("Berhasil mengimpor $count user master!");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            if ($this->command) {
                $this->command->error("Terjadi kesalahan saat import: " . $e->getMessage());
            }
        }
 
        fclose($file);
    }
}
