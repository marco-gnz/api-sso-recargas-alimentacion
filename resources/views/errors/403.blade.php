<!doctype html>
<title>Acceso denegado</title>
<style>
    body {
        text-align: center;
        padding: 100px;
        max-width: 100%;
        height: auto;
    }

    h1 {
        font-size: 50px;
    }

    body {
        font: 20px Helvetica, sans-serif;
        color: #333;
    }

    article {
        display: block;
        text-align: left;
        width: 650px;
        margin: 0 auto;
    }

    a {
        color: #dc8100;
        text-decoration: none;
    }

    a:hover {
        color: #333;
        text-decoration: none;
    }

    .logo-sso {
        width: 140px;
        height: 130px;
        image-rendering: pixelated;
        text-align: center;
    }
</style>

<img src="/img/logo-sso.jpeg" class="logo-sso" alt="Logo - SSO">
<article>
    <h1>403 - Acceso denegado</h1>
    <div>
        <p>Lo sentimos, no tienes acceso a esta página.<br>
            <a href="{{env('CLIENT_URL')}}"><p>Volver al inicio</p></a>
    </div>
</article>
