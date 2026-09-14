<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Project;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id','slug');
        $defaultProject = Project::first();

        /*
        |--------------------------------------------------------------------------
        | SUPERADMIN
        |--------------------------------------------------------------------------
        */
        $admin = User::where('username', 'admin')->orWhere('email', 'admin@local')->first();
        if (!$admin) {
            $admin = new User();
            $admin->password = Hash::make('password123');
        }
        $admin->name = 'Admin Pusat';
        $admin->username = 'admin';
        $admin->email = $admin->email ?: 'admin@local';
        $admin->phone = '081200000001';
        $admin->signature_path = 'ImageAsset/1.jpg';
        $admin->status = 'active';
        $admin->save();

        if (isset($roles['superadmin'])) {
            $admin->roles()->sync([$roles['superadmin']]);
        } elseif (isset($roles['admin'])) {
            $admin->roles()->sync([$roles['admin']]);
        }

        if ($defaultProject) {
            $admin->projects()->syncWithoutDetaching([$defaultProject->id]);
        }

        /*
        |--------------------------------------------------------------------------
        | OTHER ERP ROLE USERS
        |--------------------------------------------------------------------------
        | Standard ERP Test Accounts
        */

        $users = [
            ['email'=>'project@local','username'=>'admin_project','name'=>'Admin Project Mandau','position'=>'Admin Project','signature'=>'ImageAsset/1.jpg','role'=>'admin-project','phone'=>'081200000002'],
            ['email'=>'eva@local.com','username'=>'eva','name'=>'Eva','position'=>'Senior Admin Project','signature'=>'ImageAsset/1.jpg','role'=>'admin-project','phone'=>'081200000018'],
            ['email'=>'nikmal@example.com','username'=>'nikmal','name'=>'Nikmal Hadi','position'=>'Logistik & Gudang','signature'=>'ImageAsset/3.jpg','role'=>'logistik','phone'=>'081200000003'],
            ['email'=>'ga@local','username'=>'ga_budi','name'=>'Budi Santoso (GA)','position'=>'General Affair','signature'=>'ImageAsset/3.jpg','role'=>'general-affair','phone'=>'081200000004'],
            ['email'=>'silmi@local.com','username'=>'silmi','name'=>'Silmi','position'=>'Staff Procurement','signature'=>'ImageAsset/4.jpg','role'=>'procurement','phone'=>'081200000005'],
            ['email'=>'febri@local.com','username'=>'febri','name'=>'Febri Saputra','position'=>'Head of Procurement','signature'=>'ImageAsset/4.jpg','role'=>'procurement','phone'=>'081200000006'],
            ['email'=>'lilu@local.com','username'=>'lilu','name'=>'Lilu','position'=>'Staff Finance','signature'=>'ImageAsset/2.jpg','role'=>'finance','phone'=>'081200000007'],
            ['email'=>'melvien@example.com','username'=>'melvien','name'=>'Melvien Welang','position'=>'Finance Manager','signature'=>'ImageAsset/2.jpg','role'=>'finance','phone'=>'081200000008'],
            ['email'=>'barry@local.com','username'=>'barry','name'=>'Barry Japadarmawan','position'=>'Chief Executive Officer','signature'=>'ImageAsset/1.jpg','role'=>'ceo','phone'=>'081200000009'],
        ];

        foreach ($users as $i => $data) {
            $u = User::where('username', $data['username'])->orWhere('email', $data['email'])->first();
            if (!$u) {
                $u = new User();
                $u->password = Hash::make('password');
            }

            $u->name = $data['name'];
            $u->username = $data['username'];
            $u->email = $u->email ?: $data['email'];
            if (!empty($data['phone'])) $u->phone = $data['phone'];
            if (!empty($data['position'])) $u->position = $data['position'];
            if (!empty($data['signature'])) $u->signature_path = $data['signature'];
            $u->status = 'active';
            $u->save();

            if ($defaultProject) {
                $u->projects()->syncWithoutDetaching([$defaultProject->id]);
            }

            // Match role by slug or name
            $targetRole = Role::where('slug', $data['role'])->orWhere('name', $data['role'])->orWhere('name', ucwords(str_replace('-', ' ', $data['role'])))->first();
            if ($targetRole) {
                $u->roles()->sync([$targetRole->id]);
            }
        }
    }
}