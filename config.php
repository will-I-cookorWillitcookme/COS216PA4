
<?php
$servername = "wheatley.cs.up.ac.za";
$username = "u24911412";
$password = "X32OUO2PWHD4BJE2LORUNKIO2B6XK5WJ";
$dbname = "u24911412";
$connection = new mysqli($servername, $username, $password, $dbname);

if ($connection->connect_error) {
  die("Connection failed: " . $connection->connect_error);
}

?>