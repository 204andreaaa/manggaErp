<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id','slug');

        /*
        |--------------------------------------------------------------------------
        | SUPERADMIN
        |--------------------------------------------------------------------------
        */
        $admin = User::updateOrCreate(
            ['email' => 'admin@local'],
            [
                'name'     => 'Admin Pusat',
                'username' => 'admin',
                'phone'    => '081200000001',
                'password' => Hash::make('password123'),
                'signature_path' => 'ImageAsset/1.jpg',
                'status'   => 'active',
            ]
        );

        if (isset($roles['superadmin'])) {
            $admin->roles()->sync([$roles['superadmin']]);
        } elseif (isset($roles['admin'])) {
            $admin->roles()->sync([$roles['admin']]);
        }

        /*
        |--------------------------------------------------------------------------
        | OTHER ERP ROLE USERS
        |--------------------------------------------------------------------------
        | Standard ERP Test Accounts
        */

        $users = [
            ['email'=>'project@local','username'=>'admin_project','name'=>'Admin Project Mandau','position'=>'Admin Project','signature'=>'ImageAsset/1.jpg','role'=>'admin-project','phone'=>'081200000002'],
            ['email'=>'nikmal@example.com','username'=>'nikmal','name'=>'Nikmal Hadi','position'=>'Logistik & Gudang','signature'=>'ImageAsset/3.jpg','role'=>'logistik','phone'=>'081200000003'],
            ['email'=>'ga@local','username'=>'ga_budi','name'=>'Budi Santoso (GA)','position'=>'General Affair','signature'=>'ImageAsset/3.jpg','role'=>'general-affair','phone'=>'081200000004'],
            ['email'=>'silmi@local.com','username'=>'silmi','name'=>'Silmi','position'=>'Staff Procurement','signature'=>'ImageAsset/4.jpg','role'=>'procurement','phone'=>'081200000005'],
            ['email'=>'febri@local.com','username'=>'febri','name'=>'Febri Saputra','position'=>'Head of Procurement','signature'=>'ImageAsset/4.jpg','role'=>'procurement','phone'=>'081200000006'],
            ['email'=>'lilu@local.com','username'=>'lilu','name'=>'Lilu','position'=>'Staff Finance','signature'=>'ImageAsset/2.jpg','role'=>'finance','phone'=>'081200000007'],
            ['email'=>'melvien@example.com','username'=>'melvien','name'=>'Melvien Welang','position'=>'Finance Manager','signature'=>'ImageAsset/2.jpg','role'=>'finance','phone'=>'081200000008'],
            ['email'=>'barry@local.com','username'=>'barry','name'=>'Barry Japadarmawan','position'=>'Chief Executive Officer','signature'=>'ImageAsset/1.jpg','role'=>'ceo','phone'=>'081200000009'],
        ];

        foreach ($users as $i => $data) {
            $u = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'           => $data['name'],
                    'username'       => $data['username'],
                    'phone'          => $data['phone'],
                    'password'       => Hash::make('password'),
                    'position'       => $data['position'],
                    'signature_path' => $data['signature'],
                    'warehouse_id'   => null,
                    'status'         => 'active',
                ]
            );

            // Match role by slug or name
            $targetRole = Role::where('slug', $data['role'])->orWhere('name', $data['role'])->orWhere('name', ucwords(str_replace('-', ' ', $data['role'])))->first();
            if ($targetRole) {
                $u->roles()->sync([$targetRole->id]);
            }
        }
    }
}