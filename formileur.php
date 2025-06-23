<?php
session_start();
include_once "./config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

// Get donation box ID and validate it
$id = $_GET["id"] ?? null;
if (!$id || !is_numeric($id)) {
    echo "❌ Erreur: ID invalide.";
    exit();
}

// Get donation box information
try {
    $stmt = $pdo->prepare("SELECT * FROM boites WHERE id_boite = ?");
    $stmt->execute([$id]);
    $boite = $stmt->fetch();
    if (!$boite) {
        echo "❌ Erreur: Boîte de don introuvable.";
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "❌ Erreur de base de données.";
    exit();
}

// Get user information
$nom = $_SESSION['user_name'] ?? '';
$prenom = $_SESSION['user_prenom'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $type = $_POST['type_parrainage'] ?? '';
    $montant = null;

    // Gérer le montant
    if (isset($_POST['montant'])) {
        if ($_POST['montant'] === 'custom' && !empty($_POST['montant_personnalise'])) {
            $montant = floatval($_POST['montant_personnalise']);
        } elseif ($_POST['montant'] !== 'custom') {
            $montant = floatval($_POST['montant']);
        }
    }

    // Validation
    $errors = [];
    if (empty($type)) $errors[] = "Le type de parrainage est requis.";
    if (!$montant || $montant < 50) $errors[] = "Le montant doit être d'au moins 50 DH.";
    if ($montant > 50000) $errors[] = "Le montant ne peut pas dépasser 50,000 DH.";

    if (!empty($errors)) {
        $message = "⚠️ " . implode("<br>", $errors);
        $messageType = 'error';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert donation record
            $stmt = $pdo->prepare("
                INSERT INTO parrainages (type, montant, id_utilisateur, id_boite, nom, prenom, gmail)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $type,
                $montant,
                $_SESSION['user_id'],
                $id,
                $nom,
                $prenom,
                $email
            ]);

            // Update collected amount in boites table
            $updateStmt = $pdo->prepare("
                UPDATE boites 
                SET montant_collecte = montant_collecte + ? 
                WHERE id_boite = ?
            ");
            $updateStmt->execute([$montant, $id]);

            // Check if goal is reached and update status
            $checkStmt = $pdo->prepare("SELECT montant_collecte, objectif_financier FROM boites WHERE id_boite = ?");
            $checkStmt->execute([$id]);
            $updated_boite = $checkStmt->fetch();
            if ($updated_boite['montant_collecte'] >= $updated_boite['objectif_financier']) {
                $statusStmt = $pdo->prepare("UPDATE boites SET statut = 'Terminée' WHERE id_boite = ?");
                $statusStmt->execute([$id]);
            }

            // Commit transaction
            $pdo->commit();
            $message = "✅ Merci pour votre don de " . number_format($montant, 2) . " DH ! Votre générosité fait la différence.";
            $messageType = 'success';

            // Redirect after 3 seconds
            header("refresh:3;url=./reveir.php");
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollback();
            error_log("Donation error: " . $e->getMessage());
            $message = "❌ Erreur lors de l'enregistrement du don. Veuillez réessayer.";
            $messageType = 'error';
        }
    }
}

// Calculate progress percentage
$percentage = ($boite['montant_collecte'] / $boite['objectif_financier']) * 100;
$remaining = $boite['objectif_financier'] - $boite['montant_collecte'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de Don - <?= htmlspecialchars($boite['nom']) ?></title>
    <link rel="stylesheet" href="./css/fourmiler.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .message { 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 8px; 
            font-weight: bold;
        }
        .message.success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .message.error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .campaign-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .campaign-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #059669;
            margin-bottom: 10px;
        }
        .campaign-description {
            color: #666;
            margin-bottom: 15px;
        }
        .progress-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .progress-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }
        .progress-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #059669;
        }
        .progress-label {
            font-size: 0.9em;
            color: #666;
        }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #059669, #10b981);
            transition: width 0.3s ease;
        }
        .custom-amount {
            display: none;
            margin-top: 10px;
        }
        .custom-amount.show {
            display: block;
        }
        .amount-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .amount-input:focus {
            border-color: #059669;
            outline: none;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #059669;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="background-section">
            <img src="./images/formulaire-img.jpg" alt="Enfant souriant" class="background-image">
            <div class="background-overlay"></div>
        </div>

        <div class="form-section">
            <div class="form-container">
                <a href="./reveir.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Retour aux campagnes
                </a>

                <h1 class="form-title">Formulaire de Don</h1>
                <p class="form-subtitle">
                    Chaque don fait une différence. Merci pour votre générosité.
                </p>

                <!-- Campaign Information -->
                <div class="campaign-info">
                    <div class="campaign-title"><?= htmlspecialchars($boite['nom']) ?></div>
                    <div class="campaign-description"><?= htmlspecialchars($boite['description']) ?></div>
                    
                    <div class="progress-info">
                        <div class="progress-item">
                            <div class="progress-number"><?= number_format($boite['montant_collecte'], 2) ?> DH</div>
                            <div class="progress-label">Collecté</div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-number"><?= number_format($remaining, 2) ?> DH</div>
                            <div class="progress-label">Restant</div>
                        </div>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min($percentage, 100) ?>%"></div>
                    </div>
                    <div style="text-align: center; margin-top: 5px; color: #666;">
                        <?= number_format($percentage, 1) ?>% de l'objectif atteint
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>"><?= $message ?></div>
                <?php endif; ?>

                <form class="sponsorship-form" method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom <span class="required">*</span></label>
                            <input type="text" class="form-input" name="nom" value="<?= htmlspecialchars($nom) ?>" required readonly>
                        </div>
                        <?php if (!empty($prenom)): ?>
                        <div class="form-group">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-input" name="prenom" value="<?= htmlspecialchars($prenom) ?>" readonly>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-input" name="email" value="<?= htmlspecialchars($email) ?>" required readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type de don <span class="required">*</span></label>
                        <div class="select-wrapper">
                            <select class="form-select" name="type_parrainage" required>
                                <option value="">Sélectionnez un type</option>
                                <option value="Don ponctuel">Don ponctuel</option>
                                <option value="Parrainage mensuel">Parrainage mensuel</option>
                                <option value="Parrainage annuel">Parrainage annuel</option>
                                <option value="Don d'urgence">Don d'urgence</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Montant <span class="required">*</span></label>
                        <div class="radio-group">
                            <label><input type="radio" name="montant" value="100" required onclick="hideCustomAmount()"> 100 DH</label>
                            <label><input type="radio" name="montant" value="250" onclick="hideCustomAmount()"> 250 DH</label>
                            <label><input type="radio" name="montant" value="500" onclick="hideCustomAmount()"> 500 DH</label>
                            <label><input type="radio" name="montant" value="1000" onclick="hideCustomAmount()"> 1000 DH</label>
                            <label><input type="radio" name="montant" value="custom" onclick="showCustomAmount()"> Montant personnalisé</label>
                        </div>
                        <div class="custom-amount" id="customAmount">
                            <label class="form-label">Montant personnalisé (minimum 50 DH)</label>
                            <input type="number" class="amount-input" name="montant_personnalise" min="50" max="50000" placeholder="Entrez votre montant">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-heart"></i> Faire un Don
                    </button>
                </form>

                <div class="form-footer">
                    <p class="security-note">
                        <i class="fas fa-shield-alt"></i> Vos informations sont sécurisées et protégées.
                    </p>
                    <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                        <i class="fas fa-info-circle"></i> Vous recevrez une confirmation par email après votre don.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showCustomAmount() {
        document.getElementById('customAmount').classList.add('show');
        document.querySelector('input[name="montant_personnalise"]').required = true;
    }
    function hideCustomAmount() {
        document.getElementById('customAmount').classList.remove('show');
        document.querySelector('input[name="montant_personnalise"]').required = false;
        document.querySelector('input[name="montant_personnalise"]').value = '';
    }
    // Form validation
    document.querySelector('.sponsorship-form').addEventListener('submit', function(e) {
        const montantRadios = document.querySelectorAll('input[name="montant"]');
        const customAmount = document.querySelector('input[name="montant_personnalise"]');
        let isValid = false;
        for (let radio of montantRadios) {
            if (radio.checked) {
                if (radio.value === 'custom') {
                    if (customAmount.value && parseFloat(customAmount.value) >= 50) {
                        isValid = true;
                    }
                } else {
                    isValid = true;
                }
                break;
            }
        }
        if (!isValid) {
            e.preventDefault();
            alert('Veuillez sélectionner un montant valide (minimum 50 DH).');
        }
    });
</script>
</body>
</html>



