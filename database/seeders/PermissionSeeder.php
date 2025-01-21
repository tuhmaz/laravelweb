<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء أذونات المراقبة
        Permission::create(['name' => 'view monitoring', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'manage cache', 'guard_name' => 'sanctum']);

        // إضافة الأذونات إلى دور المشرف
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['view monitoring', 'manage cache']);
        }
    }
}
