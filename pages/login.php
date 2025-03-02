<?php
include '../includes/db_connect.php';
session_start();
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $email = trim($_POST['email']);
    $mot_de_passe = trim($_POST['mot_de_passe']);
    if (empty($role) || empty($email) || empty($mot_de_passe)) {
        $message = "Veuillez remplir tous les champs.";
    } else {
        // Préparer la requête SQL en fonction du rôle
        if ($role == "client") {
            $sql = "SELECT id, mot_de_passe FROM clients WHERE email = ?";
        } elseif ($role == "livreur") {
            $sql = "SELECT id, mot_de_passe FROM livreurs WHERE email = ?";
        } elseif ($role == "restaurant") {
            $sql = "SELECT id, mot_de_passe FROM restaurants WHERE email = ?";
        } else {
            $message = "Rôle invalide.";
        }
        if (empty($message)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    // Création de la session et redirection vers le dashboard adapté
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $role;
                    if ($role == "client") {
                        header("Location: dashboard_client.php");
                    } elseif ($role == "livreur") {
                        header("Location: dashboard_livreur.php");
                    } elseif ($role == "restaurant") {
                        header("Location: dashboard_restaurant.php");
                    }
                    exit();
                } else {
                    $message = "Mot de passe incorrect.";
                }
            } else {
                $message = "Email non trouvé.";
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
    <title>Connexion</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
<div class="container">
    <h2>Connexion</h2>
    <?php if (!empty($message)) { echo "<p id='message'>$message</p>"; } ?>
    <form method="POST" action="">
        <label for="role">Je suis :</label>
        <select name="role" id="role">
            <option value="client">Client</option>
            <option value="livreur">Livreur</option>
            <option value="restaurant">Restaurant</option>
        </select>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" required>

        <input type="submit" value="Se connecter">
    </form>
    <p>Vous n'avez pas de compte ? <a href="register.php">S'inscrire</a></p>
</div>
</body>
</html>
