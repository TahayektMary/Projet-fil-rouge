<?php
include_once "./config/db.php"; // Connexion à la base de données

$name = $email = $password = $role = "";
$nameError = $emailError = $passwordError = $roleError = $errorGeneral = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $role = trim($_POST['role'] ?? "");

    // Validation des champs
    if (empty($name)) {
        $nameError = "Le nom est requis.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Email invalide.";
    }
    if (empty($password)) {
        $passwordError = "Mot de passe requis.";
    }
    if (empty($role)) {
        $roleError = "Rôle requis.";
    }

    // Si tout est valide
    if (empty($nameError) && empty($emailError) && empty($passwordError) && empty($roleError)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO admin (NAME, EMAIL, MODE_PASSE, ROLE) VALUES (:name, :email, :password, :role)";
        $stmt = $pdo->prepare($query);

        if ($stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashedPassword, 'role' => $role])) {
            header("Location:admin.php");
            exit();
        } else {
            $errorGeneral = "Erreur lors de l'inscription.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Administrateur</title>
    <link rel="stylesheet" href="./css/register-admin.css">
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

        <!-- Right Section - Form Section -->
        <section class="form-section">
            <div class="form-container">
                <h2>Créer un compte administrateur</h2>

                <!-- Message en cas d'erreur ou de succès -->
                <?php if (!empty($errorGeneral)): ?>
                    <div class="message error"><?= htmlspecialchars($errorGeneral) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="name" class="form-label">Nom *</label>
                        <input type="text" id="name" name="name" class="form-input" value="<?= htmlspecialchars($name) ?>" required>
                        <small class="error"><?= htmlspecialchars($nameError) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($email) ?>" required>
                        <small class="error"><?= htmlspecialchars($emailError) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Mot de passe *</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                        <small class="error"><?= htmlspecialchars($passwordError) ?></small>
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Rôle *</label>
                        <select id="role" name="role" class="form-input" required>
                            <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>-- Sélectionnez un rôle --</option>
                            <option value="Administrateur" <?= $role === 'Administrateur' ? 'selected' : '' ?>>Administrateur</option>
                            <option value="Manager" <?= $role === 'Manager' ? 'selected' : '' ?>>Manager</option>
                        </select>
                        <small class="error"><?= htmlspecialchars($roleError) ?></small>
                    </div>

                    <button type="submit" class="btn-submit"><a href="./admin.php">Créer un nouveau compte</a></button>

                    <div class="form-footer">
                        <span>Vous avez déjà un compte ?</span>
                        <a href="./login.php" class="register-link">Se connecter</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
