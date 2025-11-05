<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla (opcional)
        Usuario::truncate();

        // Crear usuarios de prueba
        $usuarios = [
            [
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
                'nombre_completo' => 'Administrador Sistema',
                'rol' => 'administrador',
                'estado' => 'activo',
                'telefono' => '3001234567'
            ],
            [
                'email' => 'veterinario@example.com',
                'password' => Hash::make('vet123'),
                'nombre_completo' => 'Dr. Juan Pérez',
                'rol' => 'veterinario',
                'estado' => 'activo',
                'telefono' => '3009876543'
            ],
            [
                'email' => 'recepcion@example.com',
                'password' => Hash::make('recep123'),
                'nombre_completo' => 'María García',
                'rol' => 'recepcionista',
                'estado' => 'activo',
                'telefono' => '3005556789'
            ],
            [
                'email' => 'cliente@example.com',
                'password' => Hash::make('cliente123'),
                'nombre_completo' => 'Carlos López',
                'rol' => 'cliente',
                'estado' => 'activo',
                'telefono' => '3007778888'
            ],
            [
                'email' => 'inactivo@example.com',
                'password' => Hash::make('inactivo123'),
                'nombre_completo' => 'Usuario Inactivo',
                'rol' => 'cliente',
                'estado' => 'inactivo',
                'telefono' => '3001112222'
            ]
        ];

        foreach ($usuarios as $usuario) {
            Usuario::create($usuario);
        }

        $this->command->info(' Usuarios creados exitosamente');
        $this->command->newLine();
        $this->command->info(' Credenciales de prueba:');
        $this->command->table(
            ['Email', 'Password', 'Rol'],
            [
                ['admin@example.com', 'admin123', 'Administrador'],
                ['veterinario@example.com', 'vet123', 'Veterinario'],
                ['recepcion@example.com', 'recep123', 'Recepcionista'],
                ['cliente@example.com', 'cliente123', 'Cliente'],
            ]
        );
    }
}
