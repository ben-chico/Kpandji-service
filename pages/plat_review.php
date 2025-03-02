<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

// Vérifier que l'identifiant du plat est fourni
if (!isset($_GET['plat_id'])) {
    header("Location: dashboard_client.php");
    exit();
}
$plat_id = intval($_GET['plat_id']);
$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;

// Récupérer les avis pour ce plat (target_type = 'plat')
$sql = "SELECT note, commentaire, created_at FROM reviews WHERE target_type IN ('plat', 'restaurant') AND target_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $plat_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
$totalNote = 0;
$count = 0;
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
    $totalNote += $row['note'];
    $count++;
}
$stmt->close();

$averageNote = ($count > 0) ? round($totalNote / $count, 1) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Avis pour le plat</title>
    <link rel="stylesheet" href="../assets/css/plat_review.css">
</head>
<body>
<div class="container">
    <h2>Avis pour le plat</h2>
    <p class="average">Note moyenne : <?php echo $averageNote; ?> / 5</p>
    
    <?php if ($count > 0): ?>
        <div class="reviews-grid">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <p class="review-note"><strong>Note :</strong> <?php echo $review['note']; ?>/5</p>
                    <p class="review-comment"><strong>Commentaire :</strong> <?php echo htmlspecialchars($review['commentaire']); ?></p>
                    <small class="review-date"><?php echo $review['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-msg">Aucun avis pour ce plat pour le moment.</p>
    <?php endif; ?>
    
    <div class="buttons">
        <!-- Bouton pour laisser un avis -->
        <a href="submit_review.php?target_type=plat&plat_id=<?php echo $plat_id; ?>&restaurant_id=<?php echo $restaurant_id; ?>" class="btn">Laisser un avis</a>
        <!-- Bouton pour retourner au menu -->
        <a href="menu_restaurant.php?id=<?php echo $restaurant_id; ?>" class="btn back-btn">Retour au menu</a>
    </div>
</div>
</body>
</html>
