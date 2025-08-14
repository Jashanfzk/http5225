<?php
session_start();
function secure(){
    if(!isset($_SESSION["id"])){
        header("Location: login.php");
        die();
    }

}





?>