<?php
ob_start(); // Empêche toute sortie avant la redirection
session_start();
require './config/db.php'; // Vérifie le chemin

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT ID_ADMIN, NAME, EMAIL, MODE_PASSE, ROLE FROM admin WHERE EMAIL = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['MODE_PASSE'])) {
                    $_SESSION['user_id'] = $user['ID_ADMIN'];
                    $_SESSION['user_name'] = $user['NAME'];
                    $_SESSION['user_role'] = $user['ROLE'];

                    ob_end_clean(); // Nettoie le tampon avant redirection
                    header("Location: ./dachbord_admin.php"); // Fichier existant ?
                    exit();
                } else {
                    $error = "Mot de passe incorrect.";
                }
            } else {
                $error = "Aucun utilisateur trouvé avec cet email.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
ob_end_flush(); // Libère le tampon si rien n'a été envoyé
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Aide et Secours - Connexion</title>
    <link rel="stylesheet" href="css/admin-connect.css">
</head>
<body>
    <div class="container">
        <!-- Partie gauche -->
        <div class="left-side">
            <div class="pattern-overlay"></div>
            <div class="logo-section">
                <img src="./images/alaoun.png" alt="logo association aide et secours">
            </div>
        </div>

        <!-- Partie droite -->
        <div class="right-side">
            <div class="form-container">
                <div class="form-title">
                    <h1>Veuillez renseigner vos informations de connexion administrateur uniques ci-dessous</h1>
                </div>

                <form class="form" method="POST" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="Email" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••••••••••" required>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="error-message"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <button type="submit" class="submit-button">Connexion</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>