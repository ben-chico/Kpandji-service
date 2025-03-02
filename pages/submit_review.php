<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit();
}

include '../includes/db_connect.php';

$client_id = $_SESSION['user_id'];

// Traitement du formulaire en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_type = isset($_POST['target_type']) ? $_POST['target_type'] : '';
    $target_id   = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    $note        = isset($_POST['note']) ? intval($_POST['note']) : 0;
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

    // Validation des données
    if (empty($target_type) || $target_id == 0 || $note < 1 || $note > 5 || empty($commentaire)) {
        $error = "Veuillez remplir correctement tous les champs.";
    } else {
        $sql = "INSERT INTO reviews (client_id, target_type, target_id, note, commentaire) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiis", $client_id, $target_type, $target_id, $note, $commentaire);
        if ($stmt->execute()) {
            header("Location: plat_review.php?plat_id=" . $target_id);
            exit();
        } else {
            $error = "Erreur lors de l'enregistrement de votre avis : " . $stmt->error;
        }
        $stmt->close();
    }
}

// Récupérer les valeurs via GET pour préremplir le formulaire
$target_type = isset($_GET['target_type']) ? $_GET['target_type'] : 'plat';
if (isset($_GET['target_id'])) {
    $target_id = intval($_GET['target_id']);
} elseif (isset($_GET['plat_id'])) {
    $target_id = intval($_GET['plat_id']);
} else {
    $target_id = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laisser un avis sur le plat</title>
    <link rel="stylesheet" href="../assets/css/submit_review.css">
</head>
<body>
  <div class="container">
    <h2>Laisser un avis sur le plat</h2>
    <?php if (isset($error)) { echo "<p class='message error'>$error</p>"; } ?>
    <form method="POST" action="submit_review.php">
        <input type="hidden" name="target_type" value="<?php echo htmlspecialchars($target_type); ?>">
        <input type="hidden" name="target_id" value="<?php echo $target_id; ?>">
        
        <div class="form-group">
            <label for="note">Note (1 à 5) :</label>
            <input type="number" name="note" id="note" min="1" max="5" required>
        </div>
        
        <div class="form-group">
            <label for="commentaire">Commentaire :</label>
            <textarea name="commentaire" id="commentaire" rows="4" required></textarea>
        </div>
        
        <button type="submit" class="btn-submit">Envoyer mon avis</button>
    </form>
    <div class="link-group">
      <a href="menu_restaurant.php?id=<?php echo isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : ''; ?>" class="btn back-btn">Retour au menu</a>
    </div>
  </div>
</body>
</html>
