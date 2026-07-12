<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Buat akun Super Admin default.
     * PENTING: Ganti password di production!
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@masjidku.com'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@masjidku.com',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        $this->command->info('Super Admin created: superadmin@masjidku.com / password');
    }
}
