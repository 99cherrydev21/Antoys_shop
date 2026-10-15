<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'jefe',
                'description' => 'Control total del sistema',
            ],
            [
                'name' => 'inventarista',
                'description' => 'Gestión y reportes de inventario',
            ],
            [
                'name' => 'vendedor',
                'description' => 'Registro y consulta de ventas',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
