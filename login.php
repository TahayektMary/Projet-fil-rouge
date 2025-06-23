<?php
session_start();

// Regenerate session ID for security
session_regenerate_id(true);

include_once "./config/db.php";

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = "Token de sécurité invalide.";
        $messageType = 'error';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';

        if (!$email) {
            $message = "Email invalide.";
            $messageType = 'error';
        } elseif (empty($mot_de_passe)) {
            $message = "Mot de passe requis.";
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id_utilisateur, nom, prenom, email, mot_de_passe FROM utilisateurs WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                    // Regenerate session ID after successful login
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_name'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['login_time'] = time();

                    header('Location: ./index.php');
                    exit;
                } else {
                    $message = "Email ou mot de passe incorrect.";
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $message = "Erreur de connexion. Veuillez réessayer.";
                $messageType = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Aide et Secours - Se connecter</title>
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Main Content -->
    <main class="main-content">
        <!-- Left Section with Logo -->
        <div class="left-section">
            <div class="logo-section">
                <img src="images/alaoun.png" alt="Logo Association Aide et Secours">
            </div>
        </div>

        <!-- Right Section with Login Form -->
        <section class="form-section">
            <div class="form-container">
                <h2 class="form-title">Se connecter</h2>
                
                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= $message ?></div>
                <?php endif; ?>
                
                <form class="login-form" method="POST" action="./login.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" placeholder="Email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" name="mot_de_passe" class="form-input" placeholder="••••••••••••••" required>
                    </div>

                    <div class="forgot-password">
                        <a href="#" class="forgot-link">mot de passe oublié ?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-submit">Se connecter</button>

                    <div class="form-footer">
                        <span>Vous n'avez pas de compte ? </span>
                        <a href="./signup.php" class="register-link">Créer un compte</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

</body>
</html>