<?php
$password_to_hash = "your_admin_password"; // <-- CHANGE 'your_admin_password' to the password you want
$hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);
echo "Password: " . htmlspecialchars($password_to_hash) . "<br>";
echo "Hash: " . htmlspecialchars($hashed_password);
?>