@extends('emails.plantilla_maestra')

@section('contenido')
    <h1>Envío de cartola</h1>
    <p>Estimado(a): {{ $esquema->funcionario->nombre_completo }}</p>
    <p>Te informamos que se ha generado el acceso directo a la cartola mensual correspondiente al beneficio de alimentación
        del mes de {{ \Carbon\Carbon::createFromFormat('m', $esquema->recarga->mes_beneficio)->locale('es')->monthName }} del
        año {{ $esquema->recarga->anio_beneficio }}.</p>
    <p>Cartola:</p>
    <ul>
        <li><a href="{{ url("/funcionario/cartola/{$esquema->uuid}") }}">{{ $esquema->recarga->establecimiento->sigla }} -
                Cartola del mes {{ $esquema->recarga->mes_beneficio }}-{{ $esquema->recarga->anio_beneficio }}</a></li>
    </ul>
    <p>El acceso está encriptado, el cual podrás abrir utilizando como contraseña tu <b>RUT sin guion ni dígito
            verificador.</b></p>
    <p>En caso de dudas o consultas, dirigir correo a
        @if ($esquema->recarga->establecimiento->cod_sirh === 1025)
            <b>jorge.oyarzuns@redsalud.gob.cl</b>
        @elseif($esquema->recarga->establecimiento->cod_sirh === 1027)
            <b>jorge.oyarzuns@redsalud.gob.cl</b>
        @elseif($esquema->recarga->establecimiento->cod_sirh === 1040)
            <b>anabelen.alvarez@redsalud.gob.cl</b>
        @elseif($esquema->recarga->establecimiento->cod_sirh === 1041)
            <b>axel.vogt@redsalud.gob.cl</b>
        @elseif($esquema->recarga->establecimiento->cod_sirh === 1042)
            <b>axel.vogt@redsalud.gob.cl</b>
        @elseif($esquema->recarga->establecimiento->cod_sirh === 1043)
            <b>jorge.oyarzuns@redsalud.gob.cl</b>
        @elseif($esquema->recarga->establecimiento->cod_sirh === 1044)
            <b>jorge.oyarzuns@redsalud.gob.cl</b>
        @endif
    </p>

    <p>Se permite el acceso únicamente desde red MINSAL.</p>

    <p><i>No responder a este correo electrónico: Este mensaje se ha enviado desde una dirección exclusiva para envío de
            correos automatizados, por lo tanto, no se reciben respuestas en esta cuenta.</i></p>
    <p>Atentamente,</p>
@endsection
