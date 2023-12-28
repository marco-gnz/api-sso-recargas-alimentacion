<?php

namespace App\Console\Commands;

use App\Models\Esquema;
use App\Models\Reajuste;
use App\Models\ReajusteEstado;
use App\Models\TipoAusentismo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\Calculos\ActualizarEsquemaController;

class ImportReajustes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:reajustes {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar registros de reajustes desde un archivo CSV';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return Carbon::createFromFormat($format, $value);
        }
    }
    public function handle()
    {
        DB::beginTransaction();
        try {
            $file = $this->argument('file');
            $csvContent = file_get_contents($file);

            if ($csvContent === false) {
                $this->error("No se pudo leer el archivo: $file");
                return;
            }

            $lines = str_getcsv($csvContent, "\n");
            $headers = str_getcsv(array_shift($lines), ';');

            $data = [];

            foreach ($lines as $line) {
                // Verificar si la línea está vacía o contiene solo espacios en blanco
                if (empty(trim($line))) {
                    continue;
                }

                $row = str_getcsv($line, ';');
                $rowData = array_combine($headers, $row);
                $data[] = $rowData;
            }

            $imports = [];

            foreach ($data as $key => $row) {
                try {
                    $funcionario = User::where('rut_completo', $row['rut'])->firstOrFail();
                    if (!$funcionario) {
                        Log::info("Funcionario no encontrado en fila: {$row}");
                    }

                    $esquema = Esquema::where('user_id', $funcionario->id)->where('recarga_id', 12)->firstOrFail();
                    if (!$esquema) {
                        Log::info("Esquema no encontrado en fila: {$row}");
                    }

                    $tipo_ausentismo = TipoAusentismo::where('nombre', $row['tipo_ausentismo'])->firstOrFail();
                    if (!$tipo_ausentismo) {
                        Log::info("Tipo ausentismo no encontrado en fila: {$row}");
                    }

                    $fecha_inicio   = Carbon::parse($this->transformDate($row['fecha_inicio']))->format('Y-m-d');
                    $fecha_termino  = Carbon::parse($this->transformDate($row['fecha_termino']))->format('Y-m-d');
                    $total_dias     = (int)$row['total_dias_ajuste'];
                    $new_data = [
                        'fecha_inicio'          => $fecha_inicio,
                        'fecha_termino'         => $fecha_termino,
                        'total_dias'            => $total_dias,
                        'calculo_dias'          => $row['calculo_dias'] === 'naturales' ? 1 : 0,
                        'observacion'           => $row['observacion'],
                        'tipo_reajuste'         => 0,
                        'incremento'            => false,
                        'tipo_ausentismo_id'    => $tipo_ausentismo->id,
                        'esquema_id'            => $esquema->id,
                        'user_id'               => $funcionario->id,
                        'user_created_by'       => 513
                    ];

                    $reajuste = Reajuste::create($new_data);
                    $reajuste->fresh();
                    if ($reajuste) {
                        $reajusteEstado_pen = ReajusteEstado::create([
                            'reajuste_id'   => $reajuste->id,
                            'status'        => ReajusteEstado::STATUS_PENDIENTE,
                            'user_id'       => 513
                        ]);
                        $reajuste->fresh();
                        $reajusteEstado_apr = ReajusteEstado::create([
                            'reajuste_id'   => $reajuste->id,
                            'status'        => ReajusteEstado::STATUS_APROBADO,
                            'user_id'       => 579
                        ]);
                        $esquema            = $reajuste->esquema;
                        $cartola_controller = new ActualizarEsquemaController;
                        $cartola_controller->updateEsquemaAjustes($esquema);
                    } else {
                        Log::info("Error al crear el Reajuste. Datos: " . json_encode($new_data));
                    }
                } catch (ModelNotFoundException $e) {
                    $message = "Modelo no encontrado en fila {$key}:" . $e->getMessage();
                    Log::info($message);
                } catch (\Exception $e) {
                    $message = "Otro error durante el foreach en fila {$key}:" . $e->getMessage();
                    Log::info($message);
                }
            }
            DB::commit();
            $this->info('Registros de reajustes importados exitosamente.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::info("Error durante la importación: " . $e->getMessage());
        }
    }
}
