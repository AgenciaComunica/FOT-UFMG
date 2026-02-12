<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecretariaUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'secretaria@teste.com'],
            [
                'name' => 'Secretaria Teste',
                'password' => Hash::make('12345678'),
                'role' => User::ROLE_SECRETARIA,
                'email_verified_at' => now(),
            ]
        );
    }
}
