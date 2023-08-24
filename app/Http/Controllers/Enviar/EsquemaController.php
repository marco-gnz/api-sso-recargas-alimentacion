<?php

namespace App\Http\Controllers\Enviar;

use App\Events\CartolaEnviadaManual;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enviar\EnviarCartolaRequest;
use App\Http\Resources\Enviar\EsquemasResource;
use App\Http\Resources\Enviar\FuncionariosResource;
use App\Models\Esquema;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EsquemaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function getFuncionarios(Request $request)
    {
        try {
            $usuario_auth       = Auth::user();
            $establecimientos   = $usuario_auth->establecimientos;

            $funcionarios = User::whereHas('contratos', function ($q) use ($establecimientos) {
                $q->whereIn('establecimiento_id', $establecimientos->pluck('id'));
            })
                ->input($request->input)
                ->get();


            return response()->json(
                array(
                    'status'        => 'Success',
                    'title'         => null,
                    'message'       => null,
                    'funcionarios'  => FuncionariosResource::collection($funcionarios),
                )
            );
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function getEsquemasFuncionario($uuid)
    {
        try {
            $funcionario = User::where('uuid', $uuid)->first();

            if ($funcionario) {
                $esquemas = $funcionario->esquemas()
                    ->where('active', true)
                    ->whereHas('recarga', function ($query) {
                        $query->where('last_status', 2)
                            ->where('active', true);
                    })
                    ->get();

                return response()->json(
                    array(
                        'status'        => 'Success',
                        'title'         => null,
                        'message'       => null,
                        'esquemas'      => EsquemasResource::collection($esquemas),
                        'email'         => $funcionario->email ? $funcionario->email : null
                    )
                );
            }
        } catch (\Exception $error) {
            return $error->getMessage();
        }
    }

    public function enviarCartola(EnviarCartolaRequest $request)
    {
        try {
            $esquemas = Esquema::whereIn('uuid', $request->esquema_id)->get();
            $email = $request->email;

            if ($esquemas && $email) {
                // Dispara el evento para enviar el correo
                CartolaEnviadaManual::dispatch($esquemas, $email);

                // Realiza alguna verificación para asegurarte de que el correo se haya enviado correctamente
                $send_email = $this->verificarEnvioCorreo($email);

                if ($send_email) {
                    return response()->json([
                        'status' => 'Success',
                        'title' => null,
                        'message' => 'Cartola enviada con éxito.',
                        'send_email' => $send_email,
                    ]);
                } else {
                    // Maneja el caso en el que el envío de correo no fue exitoso
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'No se pudo enviar el correo.',
                    ]);
                }
            } else {
                // Maneja el caso en el que faltan datos necesarios para el envío de correo
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Faltan datos para el envío de correo.',
                ]);
            }
        } catch (\Exception $error) {
            // Maneja cualquier excepción que ocurra durante el envío de correo
            return response()->json([
                'status' => 'Error',
                'message' => $error->getMessage(),
            ]);
        }
    }

    private function verificarEnvioCorreo($email)
    {
        $lastError = error_get_last();
        if ($lastError !== null) {
            // Ha ocurrido un error en el envío del correo
            return false;
        }

        return true;
    }
}
