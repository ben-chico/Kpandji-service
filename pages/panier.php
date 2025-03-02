<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}

include '../includes/db_connect.php';

// Initialiser la structure du panier dans la session si elle n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = array(
        'restaurant_id' => null,
        'items' => array() // tableau de [plat_id => quantite]
    );
}

// Si on arrive ici via un formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat_id = intval($_POST['plat_id']);
    $restaurant_id = intval($_POST['restaurant_id']);
    $quantite = intval($_POST['quantite']);

    // Si le panier est vide ou si c'est le même restaurant, on peut ajouter
    if ($_SESSION['panier']['restaurant_id'] === null || $_SESSION['panier']['restaurant_id'] === $restaurant_id) {
        $_SESSION['panier']['restaurant_id'] = $restaurant_id;
        // Ajouter / Mettre à jour la quantité
        if (isset($_SESSION['panier']['items'][$plat_id])) {
            $_SESSION['panier']['items'][$plat_id] += $quantite;
        } else {
            $_SESSION['panier']['items'][$plat_id] = $quantite;
        }
        // Redirection vers la page panier en GET pour afficher
        header("Location: panier.php");
        exit();
    } else {
        // Si l'utilisateur essaie d'ajouter un plat d'un autre restaurant
        // On peut soit vider le panier précédent ou afficher un message d'erreur
        // Pour l'exemple, on va vider l'ancien panier et remplacer par le nouveau
        $_SESSION['panier']['restaurant_id'] = $restaurant_id;
        $_SESSION['panier']['items'] = array($plat_id => $quantite);
        header("Location: panier.php");
        exit();
    }
}

// Si on est en GET, on affiche le panier
// Récupérer les infos des plats pour calculer le total
$total = 0;
$items = array();

if (!empty($_SESSION['panier']['items'])) {
    $ids = implode(',', array_keys($_SESSION['panier']['items'])); // ex: "1,2,3"
    $sql = "SELECT id, nom, prix FROM plats WHERE id IN ($ids)";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $plat_id = $row['id'];
        $quantite = $_SESSION['panier']['items'][$plat_id];
        $sous_total = $row['prix'] * $quantite;
        $total += $sous_total;
        $items[] = array(
            'id' => $plat_id,
            'nom' => $row['nom'],
            'prix' => $row['prix'],
            'quantite' => $quantite,
            'sous_total' => $sous_total
        );
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mon Panier</title>
    <link rel="stylesheet" href="../assets/css/panier.css">
</head>
<body>
<div class="container">
    <h2>Mon Panier</h2>
    <?php if (empty($items)) : ?>
        <p class="empty-msg">Votre panier est vide.</p>
    <?php else : ?>
        <div class="cart-table">
            <table>
                <thead>
                    <tr>
                        <th>Plat</th>
                        <th>Prix Unitaire</th>
                        <th>Quantité</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nom']); ?></td>
                            <td><?php echo number_format($item['prix'], 2); ?> €</td>
                            <td><?php echo $item['quantite']; ?></td>
                            <td><?php echo number_format($item['sous_total'], 2); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="total-section">
            <p><strong>Total : <?php echo number_format($total, 2); ?> €</strong></p>
            <!-- Bouton pour valider la commande -->
            <form method="POST" action="valider_commande.php">
                <input type="hidden" name="action" value="valider">
                <button type="submit" class="btn-submit">Valider la commande</button>
            </form>
        </div>
    <?php endif; ?>
    <div class="back-link">
        <a href="dashboard_client.php">Retour à la liste des restaurants</a>
    </div>
</div>
</body>
</html>
