<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            InventorySeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@co.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '081200000001',
                'status' => 'active',
                'joined_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $samples = [
            ['Budi Santoso', 'budi@co.test', 'manager', 'active'],
            ['Siti Aminah', 'siti@co.test', 'staff', 'active'],
            ['Rudi Hartono', 'rudi@co.test', 'staff', 'inactive'],
            ['Dewi Lestari', 'dewi@co.test', 'manager', 'active'],
            ['Agus Salim', 'agus@co.test', 'staff', 'active'],
            ['Rina Marlina', 'rina@co.test', 'staff', 'inactive'],
            ['Joko Prabowo', 'joko@co.test', 'manager', 'active'],
            ['Maya Sari', 'maya@co.test', 'staff', 'active'],
        ];

        foreach ($samples as $i => [$name, $email, $role, $status]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'phone' => '08120000' . str_pad((string) ($i + 10), 4, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'joined_at' => now()->subDays(rand(5, 300)),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
