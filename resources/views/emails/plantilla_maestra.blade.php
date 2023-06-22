<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 50%;
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 100px;
            height: 100px;
            margin-right: 20px;
        }

        .system-title {
            text-align: center;
            flex-grow: 1;
        }

        .content {
            /* Agrega tus estilos personalizados para el contenido aquí */
        }

        .footer {
            padding: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="https://ssosorno.cl/wrdprss_minsal/wp-content/uploads/2020/07/logo2020-174x155.jpg"  alt="Logo">
            </div>
            <div class="system-title">
                <h1>SBA</h1>
            </div>
        </div>

        <div class="content">
            @yield('contenido')
        </div>

        <div class="footer">
            <p><b>SSO</b> - Depto. Gestión de las personas</p>
        </div>
    </div>
</body>
</html>
