<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'restaurant') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db_connect.php';

$restaurant_id = $_SESSION['user_id'];
$message = "";

// Traitement des actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $commande_id = intval($_POST['commande_id']);
        $action = $_POST['action'];
        
        if ($action === 'accepter') {
            // Récupérer la mission associée à cette commande qui est en attente ou déjà acceptée
            $sql_get = "SELECT id FROM missions WHERE commande_id = ? AND (statut = 'en attente' OR statut = 'acceptee')";
            $stmt_get = $conn->prepare($sql_get);
            $stmt_get->bind_param("i", $commande_id);
            $stmt_get->execute();
            $result_get = $stmt_get->get_result();
            if ($result_get->num_rows > 0) {
                $mission = $result_get->fetch_assoc();
                $mission_id = $mission['id'];
            } else {
                // Aucune mission en attente : créer une mission avec statut "acceptee"
                $sql_create = "INSERT INTO missions (commande_id, statut) VALUES (?, 'acceptee')";
                $stmt_create = $conn->prepare($sql_create);
                $stmt_create->bind_param("i", $commande_id);
                if ($stmt_create->execute()) {
                    $mission_id = $stmt_create->insert_id;
                } else {
                    $message = "Erreur lors de la création de la mission.";
                    $stmt_create->close();
                    goto FIN;
                }
                $stmt_create->close();
            }
            $stmt_get->close();
            
            // Mettre à jour la mission en "acceptee"
            $sql_update = "UPDATE missions SET statut = 'acceptee' WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $mission_id);
            if ($stmt_update->execute()) {
                // Mettre à jour la commande associée pour passer en "en route"
                $sql_update_cmd = "UPDATE commandes SET statut = 'en route' WHERE id = ?";
                $stmt_cmd = $conn->prepare($sql_update_cmd);
                $stmt_cmd->bind_param("i", $commande_id);
                $stmt_cmd->execute();
                $stmt_cmd->close();
                $message = "Commande acceptée.";
            } else {
                $message = "Erreur lors de l'acceptation de la commande.";
            }
            $stmt_update->close();
        } elseif ($action === 'refuser') {
            $sql_update = "UPDATE commandes SET statut = 'refuse' WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("i", $commande_id);
            if ($stmt->execute()) {
                $message = "Commande refusée.";
            } else {
                $message = "Erreur lors du refus de la commande.";
            }
            $stmt->close();
        } elseif ($action === 'assigner') {
            $livreur_id = intval($_POST['livreur_id']);
            $sql_update_mission = "UPDATE missions SET livreur_id = ?, statut = 'assignee' WHERE commande_id = ?";
            $stmt_mission = $conn->prepare($sql_update_mission);
            $stmt_mission->bind_param("ii", $livreur_id, $commande_id);
            if ($stmt_mission->execute()) {
                // Optionnel : mettre à jour la commande en "en route" (reste en route)
                $sql_update_cmd = "UPDATE commandes SET statut = 'en route' WHERE id = ?";
                $stmt_cmd = $conn->prepare($sql_update_cmd);
                $stmt_cmd->bind_param("i", $commande_id);
                $stmt_cmd->execute();
                $stmt_cmd->close();
                $message = "Livreur assigné avec succès.";
            } else {
                $message = "Erreur lors de l'assignation du livreur.";
            }
            $stmt_mission->close();
        }
    }
}
FIN:
// Récupérer toutes les commandes de ce restaurant
$sql_cmd = "SELECT * FROM commandes WHERE restaurant_id = ? ORDER BY id DESC";
$stmt_cmd = $conn->prepare($sql_cmd);
$stmt_cmd->bind_param("i", $restaurant_id);
$stmt_cmd->execute();
$result_cmd = $stmt_cmd->get_result();
$stmt_cmd->close();

// Récupérer la liste de tous les livreurs
$sql_livreurs = "SELECT id, nom, prenom FROM livreurs";
$res_livreurs = $conn->query($sql_livreurs);
$liste_livreurs = [];
while ($liv = $res_livreurs->fetch_assoc()) {
    $liste_livreurs[] = $liv;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Gérer les commandes</title>
  <link rel="stylesheet" href="../assets/css/gestion_commandes.css">
</head>
<body>
  <div class="container">
    <h2>Gérer les commandes</h2>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

    <?php if ($result_cmd->num_rows > 0): ?>
      <div class="cards-grid">
        <?php while ($commande = $result_cmd->fetch_assoc()): ?>
          <?php 
            // Récupérer la mission associée à la commande
            $sql_m = "SELECT * FROM missions WHERE commande_id = " . $commande['id'];
            $res_m = $conn->query($sql_m);
            $mission = ($res_m->num_rows > 0) ? $res_m->fetch_assoc() : null;
            $mission_assigned = ($mission && !empty($mission['livreur_id']));
          ?>
          <div class="card">
            <div class="card-header">
              <h3>Commande #<?php echo $commande['id']; ?></h3>
            </div>
            <div class="card-body">
              <p><strong>Statut :</strong> <?php echo htmlspecialchars($commande['statut']); ?></p>
              <p><strong>Total :</strong> <?php echo number_format($commande['total'], 2); ?> €</p>
            </div>
            <div class="card-footer">
              <?php 
              // Si la commande est en préparation, afficher les boutons Accept et Refuse
              if ($commande['statut'] === 'en preparation' || $commande['statut'] === 'en_preparation'): 
              ?>
                <div class="action-buttons">
                  <form method="POST" action="">
                    <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                    <button type="submit" name="action" value="accepter" class="btn accept-btn">Accepter</button>
                  </form>
                  <form method="POST" action="">
                    <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                    <button type="submit" name="action" value="refuser" class="btn refuse-btn">Refuser</button>
                  </form>
                </div>
              <?php endif; ?>

              <?php 
              // Si la commande est en route et qu'aucun livreur n'est assigné, afficher le formulaire d'assignation
              if ($commande['statut'] === 'en route' && (!$mission || empty($mission['livreur_id']))): 
              ?>
                <div class="assign-form">
                  <form method="POST" action="">
                    <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                    <select name="livreur_id">
                      <?php foreach ($liste_livreurs as $liv): ?>
                        <option value="<?php echo $liv['id']; ?>"><?php echo htmlspecialchars($liv['nom']) . " " . htmlspecialchars($liv['prenom']); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="assigner" class="btn assign-btn">Assigner</button>
                  </form>
                </div>
              <?php 
              // Si la mission est assignée, afficher uniquement le message
              elseif ($mission && !empty($mission['livreur_id'])):
              ?>
                <p class="assigned-msg">Livreur assigné</p>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p class="empty-msg">Aucune commande pour le moment.</p>
    <?php endif; ?>

    <div class="back-link">
      <a href="dashboard_restaurant.php" class="btn back-btn">Retour au dashboard</a>
    </div>
  </div>
</body>
</html>
