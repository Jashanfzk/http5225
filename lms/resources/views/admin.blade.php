<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
   <div>
    <nav>
        <ul>
            <li><a href="">Home</a></li>
            <li><a href="">Students</a></li>
        </ul>    
    </nav>
   </div> 
   <hr>
   <div>
    @yield('content')
   </div>
</body>
</html>