<?php

namespace App\Http\Resources;

use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EstadosReajusteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'status'                => $this->status,
            'color'                 => $this->status === 0 ? '#808080' : ($this->status === 1 ? '#7ac143' : '#ee3a44'),
            'status_nombre'         => ReajusteEstado::STATUS_NOM[$this->status],
            'observacion'           => $this->observacion != null ? $this->observacion : null,
            'user_created_by'       => $this->userCreatedBy != null ? "{$this->userCreatedBy->nombres} {$this->userCreatedBy->apellidos}" : null,
            'created_at'            => Carbon::parse($this->created_at)->format('d-m-Y H:i a'),
        ];
    }
}
