<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Soporte técnico',
                'description' => 'Problemas técnicos relacionados con el uso del sistema.',
                'is_active' => true,
            ],
            [
                'name' => 'Cuenta de usuario',
                'description' => 'Problemas de acceso, credenciales o configuración de cuenta.',
                'is_active' => true,
            ],
            [
                'name' => 'Facturación',
                'description' => 'Consultas relacionadas con pagos, facturas o cobros.',
                'is_active' => true,
            ],
            [
                'name' => 'Reportes y bugs',
                'description' => 'Errores detectados o comportamientos inesperados del sistema.',
                'is_active' => true,
            ],
            [
                'name' => 'Solicitud general',
                'description' => 'Solicitudes que no pertenecen a otra categoría específica.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}