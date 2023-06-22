<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $permissions_extras     = $this->getPermissionNames();
        $permissions_to_roles   = $this->getPermissionsViaRoles()->pluck('name');

        $permissions            = array_merge($permissions_extras->toArray() ?? [], $permissions_to_roles->toArray() ?? []);
        $antepenultimate_login  = $this->loginHistorys->isNotEmpty() ? ($this->loginHistorys->count() >= 3 ? $this->loginHistorys->nth(-3)->last() : $this->loginHistorys->last()) : null;
        $login_at               = $antepenultimate_login ? Carbon::parse($antepenultimate_login->login_at)->format('d-m-Y H:i:s') : null;

        return [
            'uuid'                  => $this->uuid,
            'rut_completo'          => $this->rut_completo,
            'nombres'               => $this->nombres,
            'apellidos'             => $this->apellidos,
            'nombre_completo'       => $this->nombre_completo,
            'email'                 => $this->email,
            'roles'                 => $this->getRoleNames(),
            'permissions'           => $permissions,
            'roles_name'            => $this->roles()->pluck('name')->implode(', '),
            'establecimientos'      => $this->establecimientos() ? $this->establecimientos()->pluck('sigla')->implode(' - ') : null,
            'last_login'            => $login_at
        ];
    }
}
