<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
    // Vérifier qu'il y a un panier et qu'il n'est pas vide
    if (empty($_SESSION['panier']['items'])) {
        // Rien à commander
        header("Location: panier.php");
        exit();
    }

    // Récupérer l'ID du client (depuis la session)
    $client_id = $_SESSION['user_id'];
    $restaurant_id = $_SESSION['panier']['restaurant_id'];

    // Calculer le total
    $total = 0;
    $items = $_SESSION['panier']['items'];

    // On récupère les infos pour calculer le total
    $ids = implode(',', array_keys($items));
    $sql_plats = "SELECT id, prix FROM plats WHERE id IN ($ids)";
    $result_plats = $conn->query($sql_plats);
    while ($row = $result_plats->fetch_assoc()) {
        $plat_id = $row['id'];
        $quantite = $items[$plat_id];
        $total += $row['prix'] * $quantite;
    }

    // 1. Créer la commande
    $statut = 'en preparation'; // par défaut
    $stmt = $conn->prepare("INSERT INTO commandes (client_id, restaurant_id, statut, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisd", $client_id, $restaurant_id, $statut, $total);
    $stmt->execute();
    $commande_id = $stmt->insert_id;
    $stmt->close();

    // 2. Créer les détails de commande
    $stmt_detail = $conn->prepare("INSERT INTO commande_details (commande_id, plat_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
    foreach ($items as $plat_id => $quantite) {
        // Récupérer le prix unitaire
        $sql_price = "SELECT prix FROM plats WHERE id = ?";
        $stmt_price = $conn->prepare($sql_price);
        $stmt_price->bind_param("i", $plat_id);
        $stmt_price->execute();
        $result_price = $stmt_price->get_result();
        $price_row = $result_price->fetch_assoc();
        $prix_unitaire = $price_row['prix'];
        $stmt_price->close();

        $stmt_detail->bind_param("iiid", $commande_id, $plat_id, $quantite, $prix_unitaire);
        $stmt_detail->execute();
    }
    $stmt_detail->close();

    // 3. Vider le panier
    $_SESSION['panier'] = array(
        'restaurant_id' => null,
        'items' => array()
    );

    // 4. Rediriger vers un message de confirmation ou le dashboard
    header("Location: dashboard_client.php?commande=success");
    exit();
} else {
    // Si on arrive ici sans passer par le formulaire
    header("Location: dashboard_client.php");
    exit();
}
