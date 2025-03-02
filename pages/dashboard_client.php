<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

// Récupérer l'ID du client depuis la session
$client_id = $_SESSION['user_id'];

// Récupérer les informations du client pour le message de bienvenue
$sql_client = "SELECT prenom FROM clients WHERE id = ?";
$stmt_client = $conn->prepare($sql_client);
$stmt_client->bind_param("i", $client_id);
$stmt_client->execute();
$result_client = $stmt_client->get_result();
if ($result_client->num_rows > 0) {
    $client_data = $result_client->fetch_assoc();
    $client_name = $client_data['prenom'];
} else {
    $client_name = "";
}
$stmt_client->close();

// Récupérer la liste des restaurants
$sql = "SELECT id, nom_restaurant, adresse, telephone FROM restaurants";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard Client</title>
    <link rel="stylesheet" href="../assets/css/dashboard_client.css">
</head>
<body>
<div class="container">
    <h2>Bienvenu dans votre espace client, <?php echo htmlspecialchars($client_name); ?> !</h2>
    
    <h3>Liste des Restaurants</h3>
    <div class="cards-grid">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='card'>";
            echo "<p><strong>" . htmlspecialchars($row['nom_restaurant']) . "</strong></p>";
            echo "<p>Adresse : " . htmlspecialchars($row['adresse']) . "</p>";
            echo "<p>Téléphone : " . htmlspecialchars($row['telephone']) . "</p>";
            // Bouton pour voir le menu
            echo "<a href='menu_restaurant.php?id=" . $row['id'] . "' class='btn'>Voir le menu</a>";
            echo "</div>";
        }
    } else {
        echo "<p>Aucun restaurant disponible.</p>";
    }
    ?>
    </div>
    
    <a href="logout.php" class="logout">Se déconnecter</a>
    <?php 
        // Récupérer les commandes du client// Afficher les commandes du client
        $sql_cmd = "SELECT * FROM commandes WHERE client_id = ? ORDER BY id DESC";
        $stmt_cmd = $conn->prepare($sql_cmd);
        $stmt_cmd->bind_param("i", $client_id);
        $stmt_cmd->execute();
        $result_cmd = $stmt_cmd->get_result();
    ?>
    <h3>Mes Commandes</h3>
    <?php
    if ($result_cmd->num_rows > 0) {
        while ($commande = $result_cmd->fetch_assoc()) {
            echo "<div class='order-card'>";
            echo "Commande #" . $commande['id'] . " | Statut : " . htmlspecialchars($commande['statut']) . " | Total : " . number_format($commande['total'], 2) . " €";
            echo " - <a href='order_details.php?commande_id=" . $commande['id'] . "'>Voir les détails</a>";
            echo "</div>";
        }
    } else {
        echo "<p>Vous n'avez pas encore passé de commande.</p>";
    }
    ?>
</div>

<!-- Script JS pour appliquer un effet de fade-in aux cartes -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  const cards = document.querySelectorAll(".card, .order-card");
  cards.forEach((card, index) => {
    setTimeout(() => {
      card.classList.add("visible");
    }, index * 100);
  });
});
</script>
</body>
</html>

