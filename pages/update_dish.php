<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'restaurant') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

$restaurant_id = $_SESSION['user_id'];
$message = "";

// Vérifier si un plat_id est passé
if (!isset($_GET['plat_id'])) {
    header("Location: manage_menu.php");
    exit();
}
$plat_id = intval($_GET['plat_id']);

// Vérifier que ce plat appartient bien au restaurant
$sql_check = "SELECT * FROM plats WHERE id = ? AND restaurant_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $plat_id, $restaurant_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows == 0) {
    header("Location: manage_menu.php");
    exit();
}
$plat = $result_check->fetch_assoc();
$stmt_check->close();

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $image_path = $plat['image'];
    $error = "";


    // Vérifier si une nouvelle image a été envoyée
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $new_name = uniqid() . '.' . $file_ext;
            $destination = '../uploads/' . $new_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = $new_name;
                // Optionnel : supprimer l'ancienne image si elle existe et n'est pas vide
                if (!empty($plat['image']) && file_exists('../uploads/' . $plat['image'])) {
                   unlink('../uploads/' . $plat['image']);
             }
            } else {
                $message = "Erreur lors du téléchargement de l'image.";
            }
        } else {
            $message = "Type d'image non autorisé. Seules les extensions jpg, jpeg, png, gif sont autorisées.";
        }
    }

    if (empty($error) && !empty($nom) && $prix > 0) {
        $sql_update = "UPDATE plats SET nom = ?, description = ?, prix = ?, image = ? WHERE id = ? AND restaurant_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssdsii", $nom, $description, $prix, $image_path, $plat_id, $restaurant_id);

        if ($stmt_update->execute()) {
            $message = "Plat mis à jour avec succès !";
            // Mettre à jour $plat pour refléter les nouvelles valeurs
            $plat['nom'] = $nom;
            $plat['description'] = $description;
            $plat['prix'] = $prix;
            $plat['image'] = $image_path;
        } else {
            $message = "Erreur lors de la mise à jour : " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $message = "Veuillez remplir tous les champs (nom et prix > 0).";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modifier le plat</title>
    <link rel="stylesheet" href="../assets/css/update_dish.css">
</head>
<body>
<div class="container">
    <h2>Modifier le plat</h2>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="nom">Nom :</label>
            <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($plat['nom']); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description :</label>
            <textarea name="description" id="description"><?php echo htmlspecialchars($plat['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="prix">Prix :</label>
            <input type="number" step="0.01" name="prix" id="prix" value="<?php echo $plat['prix']; ?>" required>
        </div>
        <div class="form-group">
            <label for="image">Modifier l'image (laisser vide pour conserver l'image actuelle) :</label>
            <input type="file" name="image" id="image" accept="image/*">
        </div>
        <div class="button-group">
            <button type="submit" class="btn-submit">Mettre à jour</button>
        </div>
    </form>
    <div class="back-link">
        <a href="gestion_menu.php" class="btn back-btn">Retour à la gestion du menu</a>
    </div>
</div>
</body>
</html>