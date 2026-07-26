<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // group => [ [name, slug, description], ... ]
        $catalog = [
            'Dashboard' => [
                ['Lihat Dashboard', 'dashboard.view', 'Mengakses halaman dashboard'],
            ],
            'Inventory' => [
                ['Lihat Inventory', 'inventory.view', 'Melihat data stok barang'],
                ['Tambah Inventory', 'inventory.create', 'Menambah item barang'],
                ['Ubah Inventory', 'inventory.edit', 'Mengubah data barang'],
                ['Hapus Inventory', 'inventory.delete', 'Menghapus item barang'],
                ['Export Inventory', 'inventory.export', 'Mengekspor data inventory'],
            ],
            'Divisi' => [
                ['Lihat Divisi', 'divisions.view', 'Melihat daftar divisi'],
                ['Tambah Divisi', 'divisions.create', 'Menambah divisi baru'],
                ['Ubah Divisi', 'divisions.edit', 'Mengubah data divisi'],
                ['Hapus Divisi', 'divisions.delete', 'Menghapus divisi'],
            ],
            'Gudang' => [
                ['Lihat Gudang', 'warehouses.view', 'Melihat daftar gudang'],
                ['Tambah Gudang', 'warehouses.create', 'Menambah gudang baru'],
                ['Ubah Gudang', 'warehouses.edit', 'Mengubah data & konfigurasi API gudang'],
                ['Hapus Gudang', 'warehouses.delete', 'Menghapus gudang'],
            ],
            'Sinkronisasi' => [
                ['Lihat Status Sync', 'sync.view', 'Melihat status & riwayat sinkronisasi'],
                ['Jalankan Sync', 'sync.run', 'Menjalankan sinkronisasi manual'],
            ],
            'Manajemen User' => [
                ['Lihat User', 'users.view', 'Melihat daftar pengguna'],
                ['Tambah User', 'users.create', 'Menambah pengguna baru'],
                ['Ubah User', 'users.edit', 'Mengubah data pengguna'],
                ['Hapus User', 'users.delete', 'Menghapus pengguna'],
            ],
            'Role' => [
                ['Lihat Role', 'roles.view', 'Melihat daftar role'],
                ['Tambah Role', 'roles.create', 'Menambah role baru'],
                ['Ubah Role', 'roles.edit', 'Mengubah role & hak akses'],
                ['Hapus Role', 'roles.delete', 'Menghapus role'],
            ],
            'Permission' => [
                ['Lihat Permission', 'permissions.view', 'Melihat daftar permission'],
                ['Tambah Permission', 'permissions.create', 'Menambah permission'],
                ['Ubah Permission', 'permissions.edit', 'Mengubah permission'],
                ['Hapus Permission', 'permissions.delete', 'Menghapus permission'],
            ],
        ];

        foreach ($catalog as $group => $perms) {
            foreach ($perms as [$name, $slug, $desc]) {
                Permission::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'group' => $group, 'description' => $desc]
                );
            }
        }

        $all = Permission::pluck('id')->all();
        $bySlug = fn (array $slugs) => Permission::whereIn('slug', $slugs)->pluck('id')->all();

        // Administrator — full access, locked
        $admin = Role::updateOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Administrator', 'description' => 'Akses penuh ke seluruh fitur sistem', 'is_locked' => true]
        );
        $admin->permissions()->sync($all);

        // Manager — kelola operasional
        $manager = Role::updateOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Mengelola inventory dan pengguna', 'is_locked' => false]
        );
        $manager->permissions()->sync($bySlug([
            'dashboard.view',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.export',
            'warehouses.view', 'warehouses.edit', 'divisions.view', 'sync.view', 'sync.run',
            'users.view', 'users.create', 'users.edit',
            'roles.view',
        ]));

        // Staff — hanya melihat
        $staff = Role::updateOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'Akses lihat data operasional', 'is_locked' => false]
        );
        $staff->permissions()->sync($bySlug([
            'dashboard.view', 'inventory.view', 'warehouses.view', 'divisions.view', 'sync.view',
        ]));
    }
}
