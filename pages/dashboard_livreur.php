<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'livreur') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

$livreur_id = $_SESSION['user_id'];

// Récupérer le nom du livreur pour l'affichage
$sql_livreur = "SELECT prenom, nom FROM livreurs WHERE id = ?";
$stmt = $conn->prepare($sql_livreur);
$stmt->bind_param("i", $livreur_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $livreur_name = $row['prenom'] . " " . $row['nom'];
} else {
    $livreur_name = "Inconnu";
}
$stmt->close();

$message = "";

// Traitement des actions via POST (pour les requêtes non AJAX, si besoin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    if (isset($_POST['action'])) {
        $commande_id = isset($_POST['commande_id']) ? intval($_POST['commande_id']) : 0;
        $action = $_POST['action'];
        if ($commande_id == 0) {
            $message = "Commande non spécifiée.";
        } else {
            if ($action === 'valider') {
                // Récupérer la mission associée à cette commande dont le statut est "acceptee", "en route" ou "assignee"
                $sql_get = "SELECT id FROM missions WHERE commande_id = ? AND (statut = 'acceptee' OR statut = 'en route' OR statut = 'assignee')";
                $stmt_get = $conn->prepare($sql_get);
                $stmt_get->bind_param("i", $commande_id);
                $stmt_get->execute();
                $result_get = $stmt_get->get_result();
                if ($result_get->num_rows > 0) {
                    $mission = $result_get->fetch_assoc();
                    $mission_id = $mission['id'];
                } else {
                    $message = "Aucune mission à valider pour cette commande.";
                    $stmt_get->close();
                    goto FIN;
                }
                $stmt_get->close();
                
                // Mettre à jour la mission en "livre"
                $sql_update_mission = "UPDATE missions SET statut = 'livre' WHERE id = ?";
                $stmt_mission = $conn->prepare($sql_update_mission);
                $stmt_mission->bind_param("i", $mission_id);
                if ($stmt_mission->execute()) {
                    // Mettre à jour la commande en "livre"
                    $sql_update_cmd = "UPDATE commandes SET statut = 'livre' WHERE id = ?";
                    $stmt_cmd = $conn->prepare($sql_update_cmd);
                    $stmt_cmd->bind_param("i", $commande_id);
                    $stmt_cmd->execute();
                    $stmt_cmd->close();
                    $message = "Mission validée, commande livrée.";
                } else {
                    $message = "Erreur lors de la validation de la mission.";
                }
                $stmt_mission->close();
            }
        }
    }
}
FIN:
// Récupérer toutes les missions du livreur dont le statut n'est pas "livre"
$sql_missions = "SELECT m.id AS mission_id, m.commande_id, m.statut, c.total, c.statut AS commande_status, r.nom_restaurant 
                 FROM missions m
                 JOIN commandes c ON m.commande_id = c.id
                 JOIN restaurants r ON c.restaurant_id = r.id
                 WHERE m.livreur_id = ? AND LOWER(TRIM(m.statut)) <> 'livre'
                 ORDER BY m.id DESC";
$stmt_missions = $conn->prepare($sql_missions);
$stmt_missions->bind_param("i", $livreur_id);
$stmt_missions->execute();
$result_missions = $stmt_missions->get_result();
$stmt_missions->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard Livreur</title>
  <link rel="stylesheet" href="../assets/css/dashboard_livreur.css">
</head>
<body>
  <div class="container">
    <h2>Bienvenue dans ton espace Livreur, <?php echo htmlspecialchars($livreur_name); ?> !</h2>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
    <h3>Mes Missions</h3>
    <?php if ($result_missions->num_rows > 0): ?>
      <div class="cards-grid">
        <?php while ($mission = $result_missions->fetch_assoc()): ?>
          <div class="card" id="mission-<?php echo $mission['mission_id']; ?>">
            <div class="card-header">
              <h3>Mission #<?php echo $mission['mission_id']; ?></h3>
            </div>
            <div class="card-body">
              <p><strong>Commande :</strong> <?php echo $mission['commande_id']; ?></p>
              <p><strong>Restaurant :</strong> <?php echo htmlspecialchars($mission['nom_restaurant']); ?></p>
              <p><strong>Total :</strong> <?php echo number_format($mission['total'], 2); ?> €</p>
              <p><strong>Statut mission :</strong> <?php echo htmlspecialchars($mission['statut']); ?></p>
              <p><strong>Statut commande :</strong> <?php echo htmlspecialchars($mission['commande_status']); ?></p>
            </div>
            <div class="card-footer">
              <?php 
              // Afficher le bouton "Valider la mission" si le statut n'est pas "livre"
              if (strtolower(trim($mission['statut'])) !== 'livre'): ?>
                <form class="validate-form" method="POST" action="">
                  <input type="hidden" name="commande_id" value="<?php echo $mission['commande_id']; ?>">
                  <button type="submit" name="action" value="valider" class="btn valider-btn">Valider la mission</button>
                </form>
              <?php else: ?>
                <p class="assigned-msg">Mission validée</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="empty-msg">Aucune mission pour le moment.</p>
    <?php endif; ?>
    <div class="back-link">
      <a href="logout.php" class="btn logout-btn">Se déconnecter</a>
    </div>
  </div>

  <!-- Script pour gérer la soumission AJAX et la transition -->
  <script>
  document.addEventListener("DOMContentLoaded", function() {
      document.querySelectorAll("form.validate-form").forEach(function(form) {
          form.addEventListener("submit", function(e) {
              e.preventDefault();
              const formData = new FormData(form);
              fetch(form.action, {
                  method: "POST",
                  body: formData,
                  headers: {
                      "X-Requested-With": "XMLHttpRequest"
                  }
              })
              .then(response => response.text())
              .then(data => {
                  // En cas de succès, faire disparaître la carte avec une transition
                  const card = form.closest(".card");
                  card.classList.add("fade-out");
                  setTimeout(() => { card.remove(); }, 500);
              })
              .catch(error => {
                  console.error("Erreur:", error);
              });
          });
      });
  });
  </script>
</body>
</html>
