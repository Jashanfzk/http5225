<!DOCTYPE html>
<html>
<head>
  <title>Cricket Players</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Legends Cricket Data</h1>

<?php
require ('connection.php');
// sql Querry to fetch player
$query = "SELECT 
            cricket_players.player_name,
            cricket_players.matches,
            cricket_players.runs,
            cricket_players.average,
            cricket_players.debut_year,
            countries.name AS country
          FROM cricket_players
          JOIN countries ON cricket_players.country_id = countries.id
          ORDER BY cricket_players.average DESC";
//Executing querry
$result = mysqli_query($connection, $query);
$counter = 0;//Counter to track number of player
// To check id query return any row
if ($result && mysqli_num_rows($result) > 0) {
  echo "<table>";   
  echo "<tr>
          <th>Player Name</th>
          <th>Country</th>
          <th>Matches</th>
          <th>Runs</th>
          <th>Average</th>
          <th>Debut Year</th>
        </tr>";
// Loop through the result set and display each player data
  while ($row = mysqli_fetch_assoc($result)) {
    $counter++;
    echo "<tr>";
    echo "<td>" . $row['player_name'] . "</td>";
    echo "<td>" . $row['country'] . "</td>";
    echo "<td>" . $row['matches'] . "</td>";
    echo "<td>" . $row['runs'] . "</td>";
    echo "<td>" . $row['average'] . "</td>";
    echo "<td>" . $row['debut_year'] . "</td>";
    echo "</tr>";
  }

  echo "</table>";
} else {
  echo "<p>No players found in the database.</p>";
}
//Total number of players
echo "<p>Total Players: " . $counter . "</p>"; 
?>

</body>
</html>
