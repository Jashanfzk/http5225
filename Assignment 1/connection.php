<?php
$connection = mysqli_connect('localhost', 'root', '', 'legendscricketdata');

if (!$connection) {
  die("Connection Failed: " . mysqli_connect_error());
}
?>
