<?php
session_start();
// Vérification du rôle client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

// Récupérer l'ID du restaurant
if (!isset($_GET['id'])) {
    // Redirection ou message d'erreur si pas d'ID
    header("Location: dashboard_client.php");
    exit();
}
$restaurant_id = intval($_GET['id']);

// Récupération des informations du restaurant (optionnel, pour l'affichage)
$sql_restaurant = "SELECT nom_restaurant FROM restaurants WHERE id = ?";
$stmt_restaurant = $conn->prepare($sql_restaurant);
$stmt_restaurant->bind_param("i", $restaurant_id);
$stmt_restaurant->execute();
$result_restaurant = $stmt_restaurant->get_result();
if ($result_restaurant->num_rows == 0) {
    // Si le restaurant n'existe pas
    header("Location: dashboard_client.php");
    exit();
}
$restaurant_info = $result_restaurant->fetch_assoc();
$stmt_restaurant->close();

// Récupération des plats
$sql_plats = "SELECT id, nom, description, prix, image FROM plats WHERE restaurant_id = ?";
$stmt_plats = $conn->prepare($sql_plats);
$stmt_plats->bind_param("i", $restaurant_id);
$stmt_plats->execute();
$result_plats = $stmt_plats->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Menu du Restaurant</title>
    <link rel="stylesheet" href="../assets/css/menu_restaurant.css">
</head>
<body>
<div class="container">
    <h2>Menu de <?php echo htmlspecialchars($restaurant_info['nom_restaurant']); ?></h2>
    <div class="cards-grid">
    <?php
    if ($result_plats->num_rows > 0) {
        while ($row = $result_plats->fetch_assoc()) {
            echo "<div class='card'>";
            if (!empty($row['image'])) {
                echo "<img src='../uploads/" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['nom']) . "' class='card-img'>";
            }
            echo "<div class='card-content'>";
            echo "<h3>" . htmlspecialchars($row['nom']) . "</h3>";
            echo "<p class='price'>" . number_format($row['prix'], 2) . " €</p>";
            echo "<p class='description'>" . htmlspecialchars($row['description']) . "</p>";
            // Formulaire pour ajouter le plat au panier
            echo "<form method='POST' action='panier.php'>";
            echo "<input type='hidden' name='plat_id' value='" . $row['id'] . "'>";
            echo "<input type='hidden' name='restaurant_id' value='" . $restaurant_id . "'>";
            echo "<label>Quantité : </label>";
            echo "<input type='number' name='quantite' value='1' min='1' required>";
            echo "<input type='submit' value='Ajouter au panier' class='btn'>";
            echo "</form>";
            // Bouton "Avis"
            echo "<a href='plat_review.php?plat_id=" . $row['id'] . "&restaurant_id=" . $restaurant_id . "' class='btn review-btn'>Avis</a>";
            echo "</div>"; // fin card-content
            echo "</div>"; // fin card
        }
    } else {
        echo "<p>Aucun plat disponible pour ce restaurant.</p>";
    }
    ?>
    </div>
    <br>
    <a href="dashboard_client.php" class="back-link">Retour à la liste des restaurants</a>
</div>
</body>
</html>
