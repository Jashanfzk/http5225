<?php
include 'connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];


    if (is_numeric($id)) {
    
        $sql = "DELETE FROM schools WHERE id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        $run = mysqli_stmt_execute($stmt);

        if ($run) {
            header("Location: index.php");
            exit();
        } else {
            echo "Error: Could not delete the record.";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Invalid ID.";
    }
} else {
    echo "ID not found in the URL.";
}
?>
