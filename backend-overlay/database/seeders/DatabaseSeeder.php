<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::updateOrCreate(['code' => 'PUSAT'], ['name' => 'Kantor Pusat', 'is_active' => true]);

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ias4u.my.id')],
            [
                'unit_id' => $unit->id,
                'name' => env('ADMIN_NAME', 'Administrator eArsip'),
                'username' => 'admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'GantiPasswordKuat123!')),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        foreach ([
            ['ADM', 'Administrasi Umum', 2, 3, 'REVIEW'],
            ['KEU', 'Keuangan', 5, 5, 'REVIEW'],
            ['SDM', 'Sumber Daya Manusia', 5, 5, 'REVIEW'],
            ['LEGAL', 'Legal dan Perjanjian', 5, 10, 'PERMANENT'],
        ] as [$code, $name, $active, $inactive, $action]) {
            DB::table('archive_classifications')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'active_retention_years' => $active, 'inactive_retention_years' => $inactive, 'final_action' => $action, 'default_security_level' => 'INTERNAL', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        DB::table('archive_locations')->updateOrInsert(
            ['code' => 'PUSAT-RARSIP-L01-R01-B01'],
            ['unit_id' => $unit->id, 'building' => 'Kantor Pusat', 'room' => 'Ruang Arsip', 'cabinet' => 'Lemari 01', 'rack' => 'Rak 01', 'box' => 'Boks 01', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
