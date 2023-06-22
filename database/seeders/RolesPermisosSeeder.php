<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** ROLES */
        Role::truncate();
        $adminSuper         = Role::create(['name' => 'ADMIN.SUPER']);
        $adminSupervisor    = Role::create(['name' => 'ADMIN.SUPERVISOR']);
        $adminEjecutivo     = Role::create(['name' => 'ADMIN.EJECUTIVO']);
        $adminJefePersonal  = Role::create(['name' => 'ADMIN.JEFE-PERSONAL']);

        /** PERMISOS */
        Permission::truncate();
        $nom_permiso         = 'user';
        $readUsuario         = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createUsuario       = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateUsuario       = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteUsuario       = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusUsuario       = Permission::create(['name' => "{$nom_permiso}.status"]);

        $nom_permiso         = 'funcionario';
        $readFuncionario     = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createFuncionario   = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateFuncionario   = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteFuncionario   = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusFuncionario   = Permission::create(['name' => "{$nom_permiso}.status"]);

        $nom_permiso        = 'recarga';
        $readRecarga        = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createRecarga      = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateRecarga      = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteRecarga      = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusRecarga      = Permission::create(['name' => "{$nom_permiso}.status"]);
        $loadRecarga        = Permission::create(['name' => "{$nom_permiso}.load"]);
        $sendRecarga        = Permission::create(['name' => "{$nom_permiso}.send"]);

        $nom_permiso         = 'contrato';
        $readContrato        = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createContrato      = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateContrato      = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteContrato      = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusContrato      = Permission::create(['name' => "{$nom_permiso}.status"]);
        $loadContrato        = Permission::create(['name' => "{$nom_permiso}.load"]);

        $nom_permiso         = 'asignacion';
        $readAsignacion      = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createAsignacion    = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateAsignacion    = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteAsignacion    = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusAsignacion    = Permission::create(['name' => "{$nom_permiso}.status"]);
        $loadAsignacion      = Permission::create(['name' => "{$nom_permiso}.load"]);

        $nom_permiso         = 'turno';
        $readTurno           = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createTurno         = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateTurno         = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteTurno         = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusTurno         = Permission::create(['name' => "{$nom_permiso}.status"]);
        $loadTurno           = Permission::create(['name' => "{$nom_permiso}.load"]);

        $nom_permiso         = 'ausentismo';
        $readAusentismo      = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createAusentismo    = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateAusentismo    = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteAusentismo    = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusAusentismo    = Permission::create(['name' => "{$nom_permiso}.status"]);
        $loadAusentismo      = Permission::create(['name' => "{$nom_permiso}.load"]);

        $nom_permiso         = 'viatico';
        $readViatico         = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createViatico       = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateViatico       = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteViatico       = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusViatico       = Permission::create(['name' => "{$nom_permiso}.status"]);
        $loadViatico         = Permission::create(['name' => "{$nom_permiso}.load"]);

        $nom_permiso        = 'esquema';
        $readEsquema        = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createEsquema      = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateEsquema      = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteEsquema      = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusEsquema      = Permission::create(['name' => "{$nom_permiso}.status"]);
        $sendEsquema        = Permission::create(['name' => "{$nom_permiso}.send"]);

        $nom_permiso        = 'regla';
        $readRegla          = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createRegla        = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateRegla        = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteRegla        = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusRegla        = Permission::create(['name' => "{$nom_permiso}.status"]);

        $nom_permiso        = 'ajuste';
        $readAjuste         = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createAjuste       = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateAjuste       = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteAjuste       = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusAjuste       = Permission::create(['name' => "{$nom_permiso}.status"]);

        $nom_permiso         = 'tarjeta';
        $readTarjeta         = Permission::create(['name' => "{$nom_permiso}.read"]);
        $createTarjeta       = Permission::create(['name' => "{$nom_permiso}.create"]);
        $updateTarjeta       = Permission::create(['name' => "{$nom_permiso}.update"]);
        $deleteTarjeta       = Permission::create(['name' => "{$nom_permiso}.delete"]);
        $statusTarjeta       = Permission::create(['name' => "{$nom_permiso}.status"]);

        /** PERMISOS TO ROLES */

        $all_permissions = Permission::all()->pluck('name');
        $adminSuper->syncPermissions($all_permissions);

        $adminSupervisor->syncPermissions([
            $readUsuario,
            $readFuncionario, $createFuncionario, $updateFuncionario, $statusFuncionario,
            $readRecarga, $updateRecarga, $statusRecarga, $loadRecarga, $sendRecarga,
            $readContrato, $createContrato, $updateContrato, $statusContrato, $loadContrato,
            $readAsignacion, $createAsignacion, $updateAsignacion, $statusAsignacion, $loadAsignacion,
            $readTurno, $createTurno, $updateTurno, $statusTurno, $loadTurno,
            $readAusentismo, $createAusentismo, $updateAusentismo, $statusAusentismo, $loadAusentismo,
            $readViatico, $createViatico, $updateViatico, $statusViatico, $loadViatico,
            $readRegla, $createRegla, $updateRegla, $statusRegla,
            $readEsquema, $createEsquema, $sendEsquema,
            $readAjuste, $createAjuste, $updateAjuste, $deleteAjuste, $statusAjuste,
            $readTarjeta, $createTarjeta, $updateTarjeta, $statusTarjeta,
        ]);

        $adminEjecutivo->syncPermissions([
            $readFuncionario, $createFuncionario, $updateFuncionario,
            $readRecarga, $statusRecarga, $loadRecarga, $sendRecarga,
            $readContrato, $createContrato, $updateContrato, $statusContrato, $loadContrato,
            $readAsignacion, $createAsignacion, $updateAsignacion, $statusAsignacion, $loadAsignacion,
            $readTurno, $createTurno, $updateTurno, $statusTurno, $loadTurno,
            $readAusentismo, $createAusentismo, $updateAusentismo, $statusAusentismo, $loadAusentismo,
            $readViatico, $createViatico, $updateViatico, $statusViatico, $loadViatico,
            $readRegla, $updateRegla, $statusRegla,
            $readEsquema, $createEsquema, $updateEsquema, $statusEsquema, $sendEsquema,
            $readAjuste, $createAjuste, $updateAjuste,
            $readTarjeta, $createTarjeta, $updateTarjeta, $deleteTarjeta, $statusTarjeta,
        ]);

        $adminJefePersonal->syncPermissions([
            $readFuncionario,
            $readRecarga,
            $readContrato,
            $readAsignacion,
            $readTurno,
            $readAusentismo,
            $readViatico,
            $readEsquema,
            $readAjuste,
            $readTarjeta
        ]);
    }
}
