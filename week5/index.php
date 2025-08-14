<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>week 5</title>
</head>
<body>
    <?php
$connect=mysqli_connect('localhost', 'root', "",'colours');
if (!$connect){
    die( "Connection failed: " .mysqli_connect_error());
}

$query ='SELECT * FROM colors';
$colours = mysqli_query($connect,$query);
//print_r($colours);
if($colours){
     foreach($colours as $color){
            $colorName=$color['Name'];
            $colorcode=$color['Hex'];
             echo"<div class ='color' style='background:$colorcode '>$colorName</div>";
            
        }
    }
 ?>
</body>
</html>