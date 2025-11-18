<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', config('app.name')) - Admin</title>
    @vite(['resources/css/main.css', 'resources/js/main.js'])
</head>
<body>
    @include('includes.admin.header')
    @include('includes.admin.sidebar')

    <main>
        @yield('content')
    </main>

    @include('includes.admin.footer')
</body>
</html>
