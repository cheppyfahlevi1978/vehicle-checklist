<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ias4u.my.id')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'username' => 'admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'GantiPasswordKuat123!')),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
    }
}
