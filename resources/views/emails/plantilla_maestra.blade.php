<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 90%;
            max-width: 600px;
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 5px auto;
            border-radius: 5px;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: -80px;
            text-align: center;
        }

        .logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .system-title {
            flex-grow: 1;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .content {
            margin-top: 20px;
            font-size: 16px;
            line-height: 1.5;
        }

        .footer {
            padding: 10px 0;
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 480px) {
            .logo {
                max-width: 60px;
            }

            .system-title {
                font-size: 16px;
            }

            .content {
                font-size: 14px;
            }

            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ asset('img/gob.svg') }}" class="logo" alt="Logo" style="width: 50px; height: auto;">
            </div>
            {{-- <div class="system-title">
                <h1>Beneficio de Alimentación</h1>
            </div> --}}
        </div>

        <div class="content">
            @yield('contenido')
        </div>

        <div class="footer">
            <p><b>SSO</b> - Depto. Gestión de las Personas</p>
        </div>
    </div>
</body>
</html>
