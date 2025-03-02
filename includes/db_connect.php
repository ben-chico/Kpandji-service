<?php
// Informations de connexion
$host = "localhost";
$dbname = "mon_app_db"; // le nom de la base de données que vous avez créée
$username = "root";     // utilisateur par défaut sur XAMPP
$password = "";         // mot de passe par défaut (souvent vide sur XAMPP)

// Création de la connexion en utilisant mysqli
$conn = new mysqli($host, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}


?>
