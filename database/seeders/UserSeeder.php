<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador del sistema
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Agente principal de soporte
        User::updateOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Support Agent',
                'password' => Hash::make('password'),
                'role' => 'support_agent',
            ]
        );

        // Segundo agente de soporte para pruebas de asignación
        User::updateOrCreate(
            ['email' => 'agent2@example.com'],
            [
                'name' => 'Second Support Agent',
                'password' => Hash::make('password'),
                'role' => 'support_agent',
            ]
        );

        // Usuario normal de prueba
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );
    }
}
