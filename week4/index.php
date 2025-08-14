<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php

   $currentHour=rand(1,23);
    $meal="";
    $food="";
    if ($currentHour >= 5 && $currentHour < 9) {
        $meal = "Breakfast";
        $food = "Bananas, Apples, and Oats";
    echo"$meal+$food";
}
    elseif($currentHour>=12 && $currentHour< 14){
        $meal="Lunch";
        $food="Fish, Chicken, and Vegetables";
        echo"$meal+$food";
    }
    elseif($currentHour>=19 && $currentHour< 21){
        $meal="dinner";
        $food="Steak, Carrots, and Broccoli";
        echo"$meal+$food";
    }

    else {
    $meal = "No Feeding Time";
    $food = " Right now The animals are not being fed.";

    }


echo"<br><br>";

$number=rand(1,1000);
 if ($number % 3 == 0 && $number % 5 == 0){
    echo"FizzBuzz";
}
 elseif ($number % 3 == 0){
    echo"Fizz";
}
 elseif ($number % 5 == 0){
    echo"Buzz";
}
 else {
    echo $number;
}
 
 
 ?>
</body>
</html>