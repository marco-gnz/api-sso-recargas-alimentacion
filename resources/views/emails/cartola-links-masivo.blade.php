@extends('emails.plantilla_maestra')

@section('contenido')
    <p><b>Estimada(o): {{ $esquema->funcionario->nombre_completo }}</b></p>
    <p>A continuación adjuntamos la cartola mensual correspondiente al beneficio de alimentación
        del mes de {{ \Carbon\Carbon::createFromFormat('m', $esquema->recarga->mes_beneficio)->locale('es')->monthName }} del
        año {{ $esquema->recarga->anio_beneficio }}.</p>
    <p>En caso de no poder descargar la cartola (PDF), por favor hacer clic en el siguiente enlace (se permite el acceso únicamente desde red MINSAL): <a href="{{ url("/funcionario/cartola/{$esquema->uuid}") }}">{{url("/funcionario/cartola/{$esquema->uuid}")}}</a></p>
    <p>El acceso a la cartola está encriptado, el cual podrás abrir utilizando como contraseña tu <b>RUT sin guion ni dígito
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

    <p><i>No responder a este correo: Este mensaje se envió desde una cuenta exclusiva para envíos automatizados y no recibe respuestas.</i></p>
    <p>Atentamente,</p>
@endsection
