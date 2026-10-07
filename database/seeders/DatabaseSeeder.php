<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Komunikasi', 'Registrasi Peserta', 'Dokumentasi', 'Desain Grafis', 'Manajemen Acara', 'Pertolongan Pertama'] as $name) {
            Skill::firstOrCreate(compact('name'));
        }foreach ([['Free', 0, 2, 30, 7], ['Standard', null, 5, 100, 30], ['Premium', null, 10, 300, 60]] as [$name,$price,$positions,$applications,$days]) {
            Package::firstOrCreate(['name' => $name], ['price' => $price, 'positions_limit' => $positions, 'applications_limit' => $applications, 'days_limit' => $days]);
        }
    }
}
