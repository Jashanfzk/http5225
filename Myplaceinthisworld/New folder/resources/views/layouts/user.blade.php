<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', config('app.name')) - User</title>
    @vite(['resources/css/main.css', 'resources/js/main.js'])
</head>
<body>
    @include('includes.user.header')
    @include('includes.user.sidebar')

    <main>
        @yield('content')
    </main>
</body>
</html>
