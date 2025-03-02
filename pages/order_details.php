<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

// Vérifier qu'un identifiant de commande est fourni
if (!isset($_GET['commande_id'])) {
    header("Location: dashboard_client.php");
    exit();
}
$commande_id = intval($_GET['commande_id']);
$client_id = $_SESSION['user_id'];

// Récupérer les informations de la commande
$sql_order = "SELECT * FROM commandes WHERE id = ? AND client_id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("ii", $commande_id, $client_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
if ($result_order->num_rows == 0) {
    echo "Commande introuvée ou non autorisée.";
    exit();
}
$order = $result_order->fetch_assoc();
$stmt_order->close();

// Récupérer les détails de la commande
$sql_details = "SELECT cd.*, p.nom, p.prix FROM commande_details cd JOIN plats p ON cd.plat_id = p.id WHERE cd.commande_id = ?";
$stmt_details = $conn->prepare($sql_details);
$stmt_details->bind_param("i", $commande_id);
$stmt_details->execute();
$result_details = $stmt_details->get_result();
$details = [];
while ($row = $result_details->fetch_assoc()) {
    $details[] = $row;
}
$stmt_details->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Détails de la commande #<?php echo $commande_id; ?></title>
    <link rel="stylesheet" href="../assets/css/order_details.css">
</head>
<body>
<div class="container">
    <h2>Détails de la commande #<?php echo $commande_id; ?></h2>
    <div class="order-info">
        <p><strong>Statut :</strong> <?php echo htmlspecialchars($order['statut']); ?></p>
        <p><strong>Total :</strong> <?php echo number_format($order['total'], 2); ?> €</p>
    </div>

    <h3>Plats commandés :</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nom du plat</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $detail): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detail['nom']); ?></td>
                    <td><?php echo $detail['quantite']; ?></td>
                    <td><?php echo number_format($detail['prix'], 2); ?> €</td>
                    <td><?php echo number_format($detail['prix'] * $detail['quantite'], 2); ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="buttons">
        <a href="dashboard_client.php" class="btn back-btn">Retour au dashboard</a>
    </div>
</div>
</body>
</html>

