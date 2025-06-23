<?php
session_start();
include_once "./config/db.php";

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = "⚠️ Token de sécurité invalide.";
        $messageType = 'error';
    } else {
        // Sanitize and validate inputs
        $nom = sanitizeInput($_POST['nom'] ?? '');
        $prenom = sanitizeInput($_POST['prenom'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $numero = sanitizeInput($_POST['numero'] ?? '');
        $age = filter_var($_POST['age'] ?? '', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 16, 'max_range' => 100]
        ]);
        $sexe = $_POST['sexe'] ?? '';
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $confirm_mdp = $_POST['confirm_mdp'] ?? '';

        // Validation
        $errors = [];
        
        if (empty($nom)) $errors[] = "Le nom est requis.";
        if (!$email) $errors[] = "Email invalide.";
        if (empty($numero) || !preg_match('/^[0-9+\-\s]+$/', $numero)) {
            $errors[] = "Numéro de téléphone invalide.";
        }
        if (!$age) $errors[] = "Âge invalide (16-100 ans).";
        if (!in_array($sexe, ['Homme', 'Femme'])) $errors[] = "Sexe invalide.";
        if (strlen($mot_de_passe) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        if ($mot_de_passe !== $confirm_mdp) $errors[] = "Les mots de passe ne correspondent pas.";

        if (!empty($errors)) {
            $message = "⚠️ " . implode("<br>", $errors);
            $messageType = 'error';
        } else {
            try {
                // Check if email already exists
                $checkStmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
                $checkStmt->execute([$email]);
                
                if ($checkStmt->fetch()) {
                    $message = "⚠️ Cet email est déjà utilisé.";
                    $messageType = 'error';
                } else {
                    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                    $sql = "INSERT INTO utilisateurs (nom, prenom, email, numero_tel, age, sexe, mot_de_passe)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nom, $prenom, $email, $numero, $age, $sexe, $hashed_password]);

                    $message = "✅ Compte créé avec succès !";
                    $messageType = 'success';
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=./login.php");
                }
            } catch (PDOException $e) {
                error_log("Signup error: " . $e->getMessage());
                $message = "❌ Erreur lors de la création du compte. Veuillez réessayer.";
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
    <title>Association Aide et Secours - Créer un compte</title>
    <link rel="stylesheet" href="./css/signup.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   
</head>
<body>
    <!-- Main Content -->
    <main class="main-content">
        <!-- Left Section with Logo -->
        <!-- Left Section with Logo -->
        <div class="left-section">
            <div class="logo-section">
                <img src="images/alaoun.png" alt="Logo Association Aide et Secours">
            </div>
        </div>

        <section class="form-section">
            <div class="form-container">
                <h2>Créer un compte</h2>

                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-input" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="prenom" class="form-input" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Numéro de portable *</label>
                        <input type="tel" name="numero" class="form-input" value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Âge *</label>
                        <input type="number" name="age" class="form-input" min="16" max="100" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sexe *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="sexe" value="Femme" <?= ($_POST['sexe'] ?? '') === 'Femme' ? 'checked' : '' ?> required> Femme</label>
                            <label><input type="radio" name="sexe" value="Homme" <?= ($_POST['sexe'] ?? '') === 'Homme' ? 'checked' : '' ?> required> Homme</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mot de passe * (min. 8 caractères)</label>
                        <input type="password" name="mot_de_passe" class="form-input" minlength="8"  placeholder="••••••••••••••" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmer mot de passe *</label>
                        <input type="password" name="confirm_mdp" class="form-input" placeholder="••••••••••••••" required>
                    </div>

                    <button type="submit" class="btn-submit">Créer un nouveau compte</button>

                    <div class="form-footer">
                        <span>Vous avez déjà un compte ?</span>
                        <a href="./login.php">Se connecter</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>