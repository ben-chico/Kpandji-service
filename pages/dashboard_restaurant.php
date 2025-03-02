<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'restaurant') {
    header("Location: ../login.php");
    exit();
}
include '../includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard Restaurant</title>
  <style>
    /* RESET DE BASE */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* STYLE DU BODY */
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f5f7fa, #c3cfe2); /* dégradé bleu clair */
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    /* CONTENEUR PRINCIPAL */
    .container {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 30px 40px;
      max-width: 500px;
      width: 100%;
      text-align: center;
      animation: fadeIn 0.8s ease forwards;
    }

    /* ANIMATION FADE IN */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* TITRE */
    h2 {
      margin-bottom: 20px;
      color: #2c3e50;
      font-size: 1.8rem;
    }

    /* LISTE DE NAVIGATION */
    ul {
      list-style: none;
      padding: 0;
      margin: 20px 0;
    }

    ul li {
      margin: 10px 0;
    }

    ul li a {
      display: block;
      text-decoration: none;
      background: #3498db;
      color: #fff;
      padding: 12px 20px;
      border-radius: 4px;
      transition: background 0.3s ease, transform 0.2s;
    }

    ul li a:hover {
      background: #2980b9;
      transform: translateY(-2px);
    }

    /* LIEN DE DÉCONNEXION */
    .logout {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      color: #e74c3c;
      font-weight: bold;
      transition: color 0.3s ease;
    }

    .logout:hover {
      color: #c0392b;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Bienvenue sur votre espace Restaurant</h2>
    <ul>
      <li><a href="gestion_menu.php">Gérer le menu</a></li>
      <li><a href="gestion_commandes.php">Gérer les commandes</a></li>
    </ul>
    <a href="logout.php" class="logout">Se déconnecter</a>
  </div>
</body>
</html>

