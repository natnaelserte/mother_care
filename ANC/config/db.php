<?php
// Database credentials
define('DB_SERVER', 'localhost'); // Or your database host
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'dph_ancis');

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>