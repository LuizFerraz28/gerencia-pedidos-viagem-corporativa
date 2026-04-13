<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'Usuário Comum',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
    }
}
