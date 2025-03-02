<?php
include '../includes/db_connect.php';
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et nettoyage des données du formulaire
    $role = $_POST['role'];
    $nom = trim($_POST['nom']);
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : "";
    $email = trim($_POST['email']);
    $adresse = trim($_POST['adresse']);
    $telephone = trim($_POST['telephone']);
    $mot_de_passe = trim($_POST['mot_de_passe']);
    $confirmer_mot_de_passe = trim($_POST['confirmer_mot_de_passe']);

    // Vérifications de base
    if (empty($role) || empty($nom) || empty($email) || empty($adresse) || empty($telephone) || empty($mot_de_passe) || empty($confirmer_mot_de_passe)) {
        $message = "Veuillez remplir tous les champs requis.";
    } elseif ($mot_de_passe !== $confirmer_mot_de_passe) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        // Hachage du mot de passe pour la sécurité
        $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        
        // Préparation de la requête SQL en fonction du rôle
        if ($role == "client") {
            $sql = "INSERT INTO clients (nom, prenom, email, adresse, telephone, mot_de_passe) VALUES (?, ?, ?, ?, ?, ?)";
        } elseif ($role == "livreur") {
            $sql = "INSERT INTO livreurs (nom, prenom, email, adresse, telephone, mot_de_passe) VALUES (?, ?, ?, ?, ?, ?)";
        } elseif ($role == "restaurant") {
            $sql = "INSERT INTO restaurants (nom_restaurant, email, adresse, telephone, mot_de_passe) VALUES (?, ?, ?, ?, ?)";
        } else {
            $message = "Rôle invalide.";
        }
        if (empty($message)) {
            // Préparation et exécution de la requête
            if ($role == "restaurant") {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $nom, $email, $adresse, $telephone, $hashed_password);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $nom, $prenom, $email, $adresse, $telephone, $hashed_password);
            }
            if ($stmt->execute()) {
                $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $message = "Erreur lors de l'inscription : " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <style>
      /* Styles spécifiques pour le multi-step */
      .form-step {
         display: none;
      }
      .form-step.active {
         display: block;
      }
      .button-group {
         display: flex;
         justify-content: space-between;
         margin-top: 20px;
      }
      /* Boutons multi-step */
      .button-group button {
         background: #7f5af0;
         color: #fff;
         border: none;
         border-radius: 4px;
         padding: 10px 15px;
         cursor: pointer;
         transition: background 0.3s ease, transform 0.2s;
      }
      .button-group button:hover {
         background: #5f3bc2;
      }
      .button-group button:active {
         transform: scale(0.98);
         opacity: 0.9;
      }
    </style>
    <script>
      // Gère l'affichage du champ "Prénom" en fonction du rôle sélectionné
        function ajusterFormulaire() {
            var role = document.getElementById("role").value;
            var prenomDiv = document.getElementById("div_prenom");
            if (role === "restaurant") {
                prenomDiv.classList.remove('show');
                prenomDiv.classList.add('hide');
            } else {
                prenomDiv.classList.remove('hide');
                prenomDiv.classList.add('show');
            }
        }
      // Gestion du formulaire multi-step
      var currentStep = 0;
      function showStep(step) {
         var steps = document.getElementsByClassName("form-step");
         for (var i = 0; i < steps.length; i++) {
            steps[i].classList.remove("active");
         }
         steps[step].classList.add("active");
         currentStep = step;
         document.getElementById("prevBtn").style.display = step === 0 ? "none" : "inline-block";
         document.getElementById("nextBtn").style.display = step === (steps.length - 1) ? "none" : "inline-block";
         document.getElementById("submitBtn").style.display = step === (steps.length - 1) ? "inline-block" : "none";
      }
      function nextStep() {
         showStep(currentStep + 1);
      }
      function prevStep() {
         showStep(currentStep - 1);
      }
      window.onload = function() {
         ajusterFormulaire();
         showStep(0);
      }
    </script>
</head>
<body>
  <div class="container">
    <h2>Inscription</h2>
    <?php if (!empty($message)) { echo "<p id='message'>$message</p>"; } ?>
    <form method="POST" action="">
      <!-- Étape 1: Rôle, Nom, Prénom, Email -->
      <div class="form-step">
         <div class="form-row">
           <div class="form-group">
             <label for="role">Je suis :</label>
             <select name="role" id="role" onchange="ajusterFormulaire();">
                <option value="client">Client</option>
                <option value="livreur">Livreur</option>
                <option value="restaurant">Restaurant</option>
             </select>
           </div>
           <div class="form-group">
             <label for="nom">Nom (ou nom du restaurant)</label>
             <input type="text" name="nom" id="nom" required>
           </div>
         </div>
         <div class="form-row">
           <div class="form-group" id="div_prenom">
             <label for="prenom">Prénom</label>
             <input type="text" name="prenom" id="prenom">
           </div>
           <div class="form-group">
             <label for="email">Email</label>
             <input type="email" name="email" id="email" required>
           </div>
         </div>
      </div>
      <!-- Étape 2: Adresse, Téléphone, Mot de passe, Confirmer mot de passe -->
      <div class="form-step">
         <div class="form-row">
           <div class="form-group">
             <label for="adresse">Adresse</label>
             <input type="text" name="adresse" id="adresse" required>
           </div>
           <div class="form-group">
             <label for="telephone">Téléphone</label>
             <input type="text" name="telephone" id="telephone" required>
           </div>
         </div>
         <div class="form-row">
           <div class="form-group">
             <label for="mot_de_passe">Mot de passe</label>
             <input type="password" name="mot_de_passe" id="mot_de_passe" required>
           </div>
           <div class="form-group">
             <label for="confirmer_mot_de_passe">Confirmer mot de passe</label>
             <input type="password" name="confirmer_mot_de_passe" id="confirmer_mot_de_passe" required>
           </div>
         </div>
      </div>
      <!-- Boutons de navigation -->
      <div class="form-row button-group full-width">
         <button type="button" id="prevBtn" onclick="prevStep()">Précédent</button>
         <button type="button" id="nextBtn" onclick="nextStep()">Suivant</button>
         <input type="submit" id="submitBtn" value="S'inscrire">
      </div>
    </form>
    <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
  </div>
</body>
</html>


