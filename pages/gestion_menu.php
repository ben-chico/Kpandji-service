<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'restaurant') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

// Récupérer l'ID du restaurant depuis la session
$restaurant_id = $_SESSION['user_id'];

$message = "";
$error = ""; // Déclare la variable pour gérer les erreurs

// --- AJOUT D'UN PLAT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);

    // Par défaut, aucune image
    $image_path = null;

    // ►►► Traitement de l'upload de l'image s'il y en a une
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = array('jpg','jpeg','png','gif');
        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $new_name = uniqid() . '.' . $file_ext;
            // Attention au chemin, ici on suppose que gestion_menu.php est dans /pages
            // et uploads/ est dans le dossier parent de /pages
            $destination = '../uploads/' . $new_name;
            if (!move_uploaded_file($file_tmp, $destination)) {
                $error = "Erreur lors du déplacement de l'image.";
            } else {
                $image_path = $new_name; // On stocke juste le nom du fichier
            }
        } else {
            $error = "Type d'image non autorisé (jpg, jpeg, png, gif).";
        }
    }

    // ►►► Vérification des champs requis
    if (empty($error) && !empty($nom) && $prix > 0) {
        $sql = "INSERT INTO plats (restaurant_id, nom, description, prix, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // 5 placeholders => "issds"
        $stmt->bind_param("issds", $restaurant_id, $nom, $description, $prix, $image_path);
        if ($stmt->execute()) {
            $message = "Plat ajouté avec succès !";
        } else {
            $message = "Erreur lors de l'ajout : " . $stmt->error;
        }
        $stmt->close();
    } else {
        // S'il manque des infos ou qu'il y a une erreur d'upload
        if (empty($message) && empty($error)) {
            $message = "Veuillez remplir tous les champs obligatoires (nom et prix).";
        }
    }
}

// --- SUPPRESSION D'UN PLAT ---
// On ne traite la suppression que si la méthode est GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $plat_id = intval($_GET['plat_id']);
    // Vérifier que le plat appartient bien à ce restaurant
    $sql_check = "SELECT id, image FROM plats WHERE id = ? AND restaurant_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $plat_id, $restaurant_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $row_check = $result_check->fetch_assoc();
        // Supprimer le plat
        $sql_delete = "DELETE FROM plats WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $plat_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Optionnel : Supprimer l'image physique si elle existe
        if (!empty($row_check['image']) && file_exists('../uploads/' . $row_check['image'])) {
            unlink('../uploads/' . $row_check['image']);
        }
        // Redirection immédiate pour nettoyer l'URL
        header("Location: gestion_menu.php");
        exit();
    } else {
        $message = "Ce plat n'existe pas ou ne vous appartient pas.";
    }
    $stmt_check->close();
}



// --- RÉCUPÉRATION DE TOUS LES PLATS DU RESTAURANT ---
$sql_plats = "SELECT id, nom, description, prix, image FROM plats WHERE restaurant_id = ?";
$stmt_plats = $conn->prepare($sql_plats);
$stmt_plats->bind_param("i", $restaurant_id);
$stmt_plats->execute();
$result_plats = $stmt_plats->get_result();
$stmt_plats->close();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Gérer le menu</title>
  <link rel="stylesheet" href="../assets/css/gestion_menu.css">
</head>
<body>
  <div class="container">
    <h2>Gérer le menu</h2>
    
    <!-- Affichage des messages -->
    <?php 
      if (!empty($message)) { 
        echo "<p style='color:green; text-align:center;'>$message</p>"; 
      }
      if (!empty($error)) { 
        echo "<p style='color:red; text-align:center;'>$error</p>"; 
      }
    ?>
    
    <!-- Formulaire d'ajout de plat -->
    <form method="POST" action="" enctype="multipart/form-data">
  <input type="hidden" name="action" value="add">
  
  <label for="nom">Nom du plat :</label>
  <input type="text" name="nom" id="nom" required>
  
  <label for="description">Description :</label>
  <textarea name="description" id="description"></textarea>
  
  <label for="image">Image du plat :</label>
  <input type="file" name="image" id="image" accept="image/*">
  
  <label for="prix">Prix :</label>
  <input type="number" step="0.01" name="prix" id="prix" required>
  
  <input type="submit" value="Ajouter le plat">
</form>

    
    <hr>
    
    <h3>Vos plats :</h3>
    
    <div class="cards-grid">
      <?php
      if ($result_plats->num_rows > 0) {
          while ($plat = $result_plats->fetch_assoc()) {
              echo "<div class='card'>";
              if (!empty($plat['image'])) {
                  echo "<img src='../uploads/" . htmlspecialchars($plat['image']) . "' alt='" . htmlspecialchars($plat['nom']) . "'>";
              }
              echo "<div class='card-content'>";
              echo "<strong>" . htmlspecialchars($plat['nom']) . "</strong><br>";
              echo "<p class='price'>" . number_format($plat['prix'], 2) . " €</p>";
              echo "<p>" . htmlspecialchars($plat['description']) . "</p>";
              echo "<a href='update_dish.php?plat_id=" . $plat['id'] . "' class='btn modify'>Modifier</a>";
              echo "<a href='gestion_menu.php?action=delete&plat_id=" . $plat['id'] . "' class='btn delete' onclick='return confirm(\"Supprimer ce plat ?\")'>Supprimer</a>";
              echo "</div>"; // fin card-content
              echo "</div>"; // fin card
          }
      } else {
          echo "<p style='text-align:center;'>Vous n'avez aucun plat pour le moment.</p>";
      }
      ?>
    </div>
    
    <div class="back-link">
      <a href="dashboard_restaurant.php">Retour au dashboard</a>
    </div>
    
  </div>
</body>
</html>

