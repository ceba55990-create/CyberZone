
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CyberZone')</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #0f1729;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        nav {
            padding: 22px 8%;
            background: #101827;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid #30334f;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .logo span {
            color: #a78bfa;
        }

        .nav-links {
            display: flex;
            gap: 26px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: #a78bfa;
        }

        main {
            flex: 1;
        }

        footer {
            background: #101827;
            padding: 40px 8% 20px;
            border-top: 1px solid #30334f;
        }

        .footer-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        footer h3 {
            margin-top: 0;
            font-size: 17px;
        }

        footer p, footer a {
            color: #94a3b8;
            font-size: 14px;
            text-decoration: none;
            line-height: 1.8;
        }

        footer a:hover {
            color: #a78bfa;
        }

        .copyright {
            border-top: 1px solid #30334f;
            padding-top: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            nav {
                flex-direction: column;
            }

            .nav-links {
                justify-content: center;
                gap: 15px;
            }

            .footer-columns {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    <nav>
        <a class="logo" href="{{ url('/') }}">
            CYBER<span>ZONE</span>
        </a>

        <div class="nav-links">
            <a href="{{ url('/') }}">Головна</a>
            <a href="{{ url('/computers') }}">Комп'ютери</a>
            <a href="{{ url('/tariffs') }}">Тарифи</a>
            <a href="{{ url('/contact') }}">Контакти</a>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer>
        <div class="footer-columns">
            <div>
                <h3>CYBERZONE</h3>
                <p>Твій простір для гри.</p>
                <p>Комп'ютерний клуб нового покоління.</p>
            </div>

            <div>
                <h3>Навігація</h3>
                <p><a href="{{ url('/') }}">Головна</a></p>
                <p><a href="{{ url('/tariffs') }}">Тарифи</a></p>
            </div>

            <div>
                <h3>Контакти</h3>
                <p>info@cyberzone.example</p>
                <p>Київ, Україна</p>
            </div>
        </div>

<div class="copyright">
    © {{ date('Y') }} CyberZone — Навчальний проєкт | КПІ
    <p>
        Працює на Laravel, Composer і залишках мого терпіння :|
    </p>
</div>
    </footer>
<!--
    CyberZone — лабораторна робота №1

    Статус системи: працює.
    Статус студента: невідомий.
    Кількість витрачених нервових клітин: NULL.
    Причина: переповнення лічильника.

    TODO: Дожити до захисту ДКР.
-->
</body>
</html>
