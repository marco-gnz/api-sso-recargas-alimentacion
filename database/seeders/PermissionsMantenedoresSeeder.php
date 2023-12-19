<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsMantenedoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $nom_permiso            = 'mantenedor';
        $readMantenedor         = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createMantenedor       = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateMantenedor       = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteMantenedor       = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusMantenedor       = Permission::create(['name' => "{$nom_permiso}.status"]);

        $adminSuper             = Role::findByName('ADMIN.SUPER');

        $adminSuper->permissions()->attach([
            $readMantenedor->id, $createMantenedor->id, $updateMantenedor->id, $deleteMantenedor->id, $statusMantenedor->id
        ]);
    }
}
