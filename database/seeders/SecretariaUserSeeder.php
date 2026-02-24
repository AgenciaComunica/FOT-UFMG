<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecretariaUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Admin Teste',
                'password' => Hash::make('12345678'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}
