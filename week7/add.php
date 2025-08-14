<?php
  if(isset($_POST['AddSchool'])){
    //Array ( [BoardName] => Garys School [SchoolName] => Gary public shhcool [AddSchool] => Add School )
    $BoardName = $_POST['BoardName'];
    $SchoolName = $_POST['SchoolName'];

    require('connect.php');
    $query = "INSERT INTO schools 
                (`Board Name`, `School Name`) 
              VALUES ('$BoardName', '$SchoolName')";
    $school = mysqli_query($connect, $query);
    //print_r($school);
    if($school){
      header('Location: index.php');
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add School</title>
</head>
<body>
  <h1>Add a School</h1>

  <form action="add.php" method="POST">
    <input type="text" name="BoardName" placeholder="Board Name">
    <input type="text" name="SchoolName" placeholder="School Name">
    <input type="submit" value="Add School" name="AddSchool">
  </form>

</body>
</html>