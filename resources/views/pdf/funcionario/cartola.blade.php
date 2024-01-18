<!doctype html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>SBA | Cartola resumen</title>
    <link rel="shortcut icon" href="{{ public_path('img/logo-sso.jpeg') }}">

    <style type="text/css">
        @page {
            margin: 1%;
        }

        body {
            margin: auto;
            padding: 0%;
            font-family: "Times New Roman", Times, serif;
            width: 100%;
        }

        .filtro {
            filter: grayscale(4%);
        }

        * {
            font-family: Verdana, Arial, sans-serif;
            font-size: 13px;
        }

        .information {
            background-color: #ffffff;
            color: black;
        }

        .information .logo {
            margin: 5px;
        }

        .datos {
            /* margin-left: 40rem; */
            padding: 0rem;
        }

        table,
        th,
        td {
            text-align: justify;
            width: 100%;
            margin-right: 10px;
        }

        .logo {
            position: relative;

            top: 0px;
            left: 20px;
            width: 50;
            height: 50;
            border-radius: 1%;
            margin: 30%;
            display: block;
        }

        .detalle {
            text-align: justify;
        }

        .title {
            text-align: left;
            font-weight: bold;
            font-size: 16px;
        }

        .section-borde {
            border: 1px solid #d1d1d1;
            margin-top: 20px;
        }

        .section-position {
            margin-left: 10px;
        }

        table.table-datos-contractuales,
        table.table-datos-contractuales th,
        table.table-datos-contractuales td {
            border: 0.3px solid black;
            border-collapse: collapse;
            border-color: #96D4D4;
            margin-bottom: 10px;
            margin-left: -5px;
        }
        @media print {
            .page-break {
                page-break-before: always;
            }
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;

            /** Estilos extra personales **/
            background-color: #0367b2;
            color: white;
            text-align: center;
            line-height: 1.5cm;
        }
    </style>

<body>
    <div class="information">
        <table width="100%">
            <tr>
                <td align="left" style="width: 10%;">
                    <img class="center logo" src="{{ public_path('img/logo-sso.jpeg') }}">
                </td>
                <td align="left" style="width: 60%;">
                    <h6 style="font-size: 11px;">DIRECCIÓN<br>
                        <small style="font-size: 9px;" class="text-muted ml-4">SUBDIRECCIÓN DE RECURSOS
                            HUMANOS</small><br>
                        <small style="font-size: 9px;" class="text-muted ml-4">DEPTO. GESTIÓN DE LAS PERSONAS</small>
                    </h6>
                </td>
            </tr>
        </table>
    </div>
    <div class="titulo">
        <h4 align="center">Cartola Resumen de Beneficio de Alimentación</h4>
        <h1 align="center">Emitida el {{$titulo_cartola->fecha_emision}}</h1>
    </div>
    <div class="datos">
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Antecedentes personales</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>RUN:</th>
                            <td>{{ $esquema->funcionario->rut_completo }}</td>
                        </tr>
                        <tr>
                            <th>Nombre:</th>
                            <td>{{ $esquema->funcionario->nombre_completo }}</td>
                        </tr>
                        <tr>
                            <th>Beneficio:</th>
                            <td>{{ $esquema->active ? 'Si' : 'No'}}</td>
                        </tr>
                        <tr>
                            <th>Turno:</th>
                            <td>{{$esquema->es_turnante_value ? 'Si' : 'No'}}</td>
                        </tr>
                        <tr>
                            <th>Reemplazante:</th>
                            <td>{{ $esquema->es_remplazo ? 'Si' : 'No'}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Detalle de beneficio de alimentación</h3>
                <table>
                    <tbody>
                        <tr>
                            <th>Establecimiento:</th>
                            <td>{{ $titulo_cartola->establecimiento }}</td>
                        </tr>
                        <tr>
                            <th>Periodo de cálculo de ausentismos:</th>
                            <td>{{ $titulo_cartola->mes_calculo }} / {{ $titulo_cartola->anio_calculo }}</td>
                        </tr>
                        <tr>
                            <th>Periodo de beneficio:</th>
                            <td>{{ $titulo_cartola->mes_beneficio }} / {{ $titulo_cartola->anio_beneficio }}</td>
                        </tr>
                        <tr>
                            <th>Monto a cancelar por día:</th>
                            <td>{{ $titulo_cartola->monto_dia }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Datos contractuales</h3>
                @if (count($esquema->contratos) > 0)
                    <table class="table-datos-contractuales">
                        <thead>
                            <th>Fecha en periodo de beneficio</th>
                            <th>Días</th>
                            <th>Días hábiles</th>
                            <th>Unidad</th>
                        </thead>
                        <tbody>
                            @foreach ($esquema->contratos as $contrato)
                                <tr>
                                    <td>{{ $contrato->fecha_inicio_periodo != null ? Carbon\Carbon::parse($contrato->fecha_inicio_periodo)->format('d-m-Y') : '--' }}
                                        a
                                        {{ $contrato->fecha_termino_periodo != null ? Carbon\Carbon::parse($contrato->fecha_termino_periodo)->format('d-m-Y') : '--' }}
                                    </td>
                                    <td>{{$contrato->total_dias_contrato_periodo ? round($contrato->total_dias_contrato_periodo) : '--'}}</td>
                                    <td>{{$contrato->total_dias_habiles_contrato_periodo ? round($contrato->total_dias_habiles_contrato_periodo) : '--'}}</td>
                                    <td>{{ $contrato->unidad != null ? $contrato->unidad->nombre : '--' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No registra contratos en el periodo de beneficio.</p>
                @endif
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Detalle de turnos en periodo de contrato</h3>
                @if (count($esquema->asistencias) > 0)
                    <table class="table-datos-contractuales">
                        <thead>
                            <td>Total Turno Largo (L)</td>
                            <td>Total Turno Nocturno (N)</td>
                            <td>Total Días Libres (X)</td>
                            <th>Total L y N</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $esquema->total_dias_turno_largo_en_periodo_contrato }}</td>
                                <td>{{ $esquema->total_dias_turno_nocturno_en_periodo_contrato }}</td>
                                <td>{{ $esquema->total_dias_libres_en_periodo_contrato }}</td>
                                <td><strong>{{ $esquema->calculo_turno }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                @elseif (count($esquema->asistencias) <= 0 && $esquema->es_turnante === 2)
                    <p>No corresponde registro de turnos en el periodo de beneficio.</p>
                @else
                    <p>No registra turnos en el periodo de beneficio.</p>
                @endif
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Variables de descuento</h3>
                <h1>Ausentismos</h1>
                @if (count($esquema->ausentismos) > 0)
                    <table class="table-datos-contractuales">
                        <thead>
                            <th>Fecha en periodo</th>
                            <th>Días</th>
                            <th>Días hábiles</th>
                            <th>Tipo de ausentismo</th>
                            <th>Descuento</th>
                        </thead>
                        <tbody>
                            @foreach ($esquema->ausentismos as $ausentismo)
                                <tr>
                                    <td>{{ $ausentismo->fecha_inicio_periodo != null ? Carbon\Carbon::parse($ausentismo->fecha_inicio_periodo)->format('d-m-Y') : '--' }}
                                        a
                                        {{ $ausentismo->fecha_termino_periodo != null ? Carbon\Carbon::parse($ausentismo->fecha_termino_periodo)->format('d-m-Y') : '--' }}
                                    </td>
                                    <td>{{ $ausentismo->total_dias_ausentismo_periodo ? round($ausentismo->total_dias_ausentismo_periodo) : '--' }}</td>
                                    <td>{{ $ausentismo->total_dias_habiles_ausentismo_periodo ? round($ausentismo->total_dias_habiles_ausentismo_periodo) : '--' }}</td>
                                    <td>{{ $ausentismo->tipoAusentismo != null ? $ausentismo->tipoAusentismo->nombre : '--' }}
                                        <td>{{ $ausentismo->tiene_descuento ? 'Si' : 'No' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No registra ausentismos en el periodo de cálculo de ausentismos.</p>
                @endif
                <h1>Viáticos</h1>
                @if (count($esquema->viaticos) > 0)
                    <table class="table-datos-contractuales">
                        <thead>
                            <th>Fecha en periodo</th>
                            <th>Días</th>
                            <th>Días hábiles</th>
                            <th>Jornada</th>
                            <th>Valor</th>
                            <th>Descuento</th>
                        </thead>
                        <tbody>
                            @foreach ($esquema->viaticos as $viatico)
                                <tr>
                                    <td>{{ $viatico->fecha_inicio_periodo != null ? Carbon\Carbon::parse($viatico->fecha_inicio_periodo)->format('d-m-Y') : '--' }}
                                        a
                                        {{ $viatico->fecha_termino_periodo != null ? Carbon\Carbon::parse($viatico->fecha_termino_periodo)->format('d-m-Y') : '--' }}
                                    </td>
                                    <td>{{ $viatico->total_dias_periodo }}</td>
                                    <td>{{ $viatico->total_dias_habiles_periodo }}</td>
                                    <td>{{ $viatico->jornada }}</td>
                                    <td>${{number_format($viatico->valor_viatico, 0, ",", ".")}}</td>
                                    <td>{{ $viatico->valor_viatico > 0 ? 'Si' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No registra viáticos en el periodo de beneficio.</p>
                @endif
            </div>
        </section>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Ajustes por administración</h3>
                @if (count($esquema->reajustes) > 0)
                    <table class="table-datos-contractuales">
                        <thead>
                            <th>Fecha</th>
                            <th>Rebaja/Incremento</th>
                            <th>Causal</th>
                            <th>Días</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                        </thead>
                        <tbody>
                            @foreach ($esquema->reajustes as $reajuste)
                            <tr>
                                <td>{{ $reajuste->fecha_inicio != null ? Carbon\Carbon::parse($reajuste->fecha_inicio)->format('d-m-y') : '--' }}
                                    a
                                    {{ $reajuste->fecha_termino != null ? Carbon\Carbon::parse($reajuste->fecha_termino)->format('d-m-y') : '--' }}
                                </td>
                                <td>{{ $reajuste->incremento ? 'Incremento' : 'Rebaja' }}</td>
                                <td>
                                    @if ($reajuste->incremento)
                                        {{ $reajuste->tipoIncremento != null ? $reajuste->tipoIncremento->nombre : '--' }}
                                    @else
                                        {{ $reajuste->tipoAusentismo != null ? $reajuste->tipoAusentismo->nombre : '--' }}
                                    @endif
                                </td>
                                <td>
                                    {{ $reajuste->total_dias ? $reajuste->total_dias : '--' }}
                                </td>
                                <td>{{ App\Models\Reajuste::TYPE_NOM[$reajuste->tipo_reajuste] }}</td>
                                <td>{{ $reajuste->monto_ajuste ? "$".number_format($reajuste->monto_ajuste, 0, ",", ".") : '--' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>Observaciones:</p>
                    <ol>
                        @foreach ($esquema->reajustes as $reajuste)
                            <li>{{$reajuste->observacion ? $reajuste->observacion : '--'}}
                        @endforeach
                    </ol>
                @else
                    <p>No registra ajustes por administración en el periodo de beneficio.</p>
                @endif
            </div>
        </section>
        <p class="page-break"></p>
        <section class="section-borde">
            <div class="section-position">
                <h3 class="title">Detalle final</h3>
                <table class="table-datos-contractuales">
                    <thead>
                        <th>#</th>
                        <th>Total días</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total días contrato</td>
                            <td>{{$esquema->calculo_contrato}}</td>
                        </tr>
                        <tr>
                            <td>Total días turno</td>
                            <td>{{$esquema->calculo_turno}}</td>
                        </tr>
                        <tr>
                            <td>Total días ausentismos</td>
                            <td>{{$esquema->calculo_grupo_uno + $esquema->calculo_grupo_dos + $esquema->calculo_grupo_tres}}</td>
                        </tr>
                        <tr>
                            <td>Total días cometidos</td>
                            <td>{{$esquema->calculo_viaticos}}</td>
                        </tr>
                        <tr>
                            <td>Total días ajustes</td>
                            <td>{{$esquema->calculo_dias_ajustes}}</td>
                        </tr>
                        <tr>
                            <td>Total ajuste de montos</td>
                            <td>${{number_format($esquema->total_monto_ajuste, 0, ",", ".")}}</td>
                        </tr>
                        <tr>
                            <td>Total días a cancelar</td>
                            <td>{{$esquema->total_dias_cancelar}}</td>
                        </tr>
                        <tr>
                            <td>Total monto</td>
                            <td>${{number_format($esquema->monto_total_cancelar, 0, ",", ".")}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</head>
