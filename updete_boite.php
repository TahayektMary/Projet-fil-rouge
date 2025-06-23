<?php
session_start();
include_once "./config/db.php";


$message = "";
$messageType = "";
$boite = null;
$boite_id = $_GET['id'] ?? null;


if (!$boite_id || !is_numeric($boite_id)) {
    $_SESSION['error_message'] = "ID de boîte invalide.";
    header("Location: ./dashboard_admin.php");
    exit();
}


try {
    $stmt = $pdo->prepare("SELECT * FROM boites WHERE id_boite = ?");
    $stmt->execute([$boite_id]);
    $boite = $stmt->fetch();
    
    if (!$boite) {
        $_SESSION['error_message'] = "Boîte de don introuvable.";
        header("Location: ./dashboard_admin.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur de base de données.";
    header("Location: ./dashboard_admin.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = " Token de sécurité invalide.";
        $messageType = 'error';
    } else {

        $nom = trim($_POST['nom'] ?? '');
        $objectif_financier = filter_var($_POST['objectif_financier'] ?? '', FILTER_VALIDATE_FLOAT);
        $description = trim($_POST['description'] ?? '');
        $statut = trim($_POST['statut'] ?? '');
        
  
        $errors = [];
        
        if (empty($nom)) {
            $errors[] = "Le nom de la boîte est requis.";
        } elseif (strlen($nom) < 3) {
            $errors[] = "Le nom de la boîte doit contenir au moins 3 caractères.";
        } elseif (strlen($nom) > 100) {
            $errors[] = "Le nom de la boîte ne peut pas dépasser 100 caractères.";
        }
        
        if (!$objectif_financier || $objectif_financier <= 0) {
            $errors[] = "L'objectif financier doit être un montant valide supérieur à 0.";
        } elseif ($objectif_financier > 10000000) {
            $errors[] = "L'objectif financier ne peut pas dépasser 10,000,000 DH.";
        }
        
        if (empty($description)) {
            $errors[] = "La description est requise.";
        } elseif (strlen($description) < 10) {
            $errors[] = "La description doit contenir au moins 10 caractères.";
        } elseif (strlen($description) > 1000) {
            $errors[] = "La description ne peut pas dépasser 1000 caractères.";
        }
        
        if (!in_array($statut, ['Active', 'Terminée'])) {
            $errors[] = "Statut invalide.";
        }
        
     
        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM boites WHERE nom = ? AND id_boite != ?");
            $checkStmt->execute([$nom, $boite_id]);
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Une autre boîte avec ce nom existe déjà.";
            }
        } catch (PDOException $e) {
            error_log("Database check error: " . $e->getMessage());
            $errors[] = "Erreur lors de la vérification du nom.";
        }
        
        // Handle image upload
        $image_path = $boite['image'] ?? null; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.";
            } elseif ($file_size > $max_size) {
                $errors[] = "La taille du fichier ne doit pas dépasser 10 Mo.";
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = './uploads/boites/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $errors[] = "Impossible de créer le dossier de téléchargement.";
                    }
                }
                
                if (empty($errors)) {
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'boite_' . uniqid() . '_' . time() . '.' . strtolower($file_extension);
                    $new_image_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $new_image_path)) {
                        // Delete old image if it exists
                        if ($image_path && file_exists($image_path)) {
                            unlink($image_path);
                        }
                        $image_path = $new_image_path;
                    } else {
                        $errors[] = "Erreur lors du téléchargement de l'image.";
                    }
                }
            }
        }

        if (!empty($errors)) {
            $message = "⚠️ " . implode("<br>", $errors);
            $messageType = 'error';
        } else {
            try {
                // Get the actual table structure
                $columnsStmt = $pdo->query("DESCRIBE boites");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Build the UPDATE query based on available columns
                $updateFields = [
                    'nom = ?',
                    'description = ?',
                    'objectif_financier = ?',
                    'statut = ?'
                ];
                $updateValues = [$nom, $description, $objectif_financier, $statut];
                
                // Add image column if it exists
                if (in_array('image', $columns)) {
                    $updateFields[] = 'image = ?';
                    $updateValues[] = $image_path;
                } elseif (in_array('image_path', $columns)) {
                    $updateFields[] = 'image_path = ?';
                    $updateValues[] = $image_path;
                }
                
                // Add the ID for WHERE clause
                $updateValues[] = $boite_id;
                
                // Build the SQL query
                $sql = "UPDATE boites SET " . implode(', ', $updateFields) . " WHERE id_boite = ?";
                $stmt = $pdo->prepare($sql);
                
                $result = $stmt->execute($updateValues);

                if ($result) {
                    $message = "✅ Boîte de don '" . htmlspecialchars($nom) . "' modifiée avec succès !";
                    $messageType = 'success';
                    
                    // Log admin action
                    try {
                        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, date_action) VALUES (?, ?, ?, NOW())");
                        $logStmt->execute([
                            $_SESSION['user_id'],
                            'UPDATE_BOITE',
                            "Modification de la boîte: " . $nom . " (ID: " . $boite_id . ")"
                        ]);
                    } catch (PDOException $e) {
                        // Log error but don't stop the process
                        error_log("Admin log error: " . $e->getMessage());
                    }
                    
                    // Update the boite array with new values
                    $boite['nom'] = $nom;
                    $boite['description'] = $description;
                    $boite['objectif_financier'] = $objectif_financier;
                    $boite['statut'] = $statut;
                    if (isset($boite['image'])) {
                        $boite['image'] = $image_path;
                    }
                    
                    // Redirect after 3 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = './dashboard_admin.php';
                        }, 3000);
                    </script>";
                } else {
                    $message = "❌ Erreur lors de la modification de la boîte.";
                    $messageType = 'error';
                }
                
            } catch (PDOException $e) {
                error_log("Box update error: " . $e->getMessage());
                $message = "❌ Erreur lors de la modification de la boîte: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get admin info for display
try {
    $adminStmt = $pdo->prepare("SELECT nom, prenom FROM users WHERE id_user = ?");
    $adminStmt->execute([$_SESSION['user_id']]);
    $admin = $adminStmt->fetch();
} catch (PDOException $e) {
    $admin = ['nom' => 'Admin', 'prenom' => ''];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Modifier <?= htmlspecialchars($boite['nom']) ?> - Association Aide et Secours</title>
    <link rel="stylesheet" href="./css/updete_boite.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">

        <aside class="sidebar">
            <div class="logo-container">
                <img src="./images/alaoun.png" alt="Association Aide et Secours" class="logo">
            </div>
            <hr>
            <nav class="nav-menu">
                <div class="nav-item active" onclick="location.href='./dachbord_admin.php'">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="7" height="7" rx="1" fill="white" />
                            <rect x="3" y="14" width="7" height="7" rx="1" fill="white" />
                            <rect x="14" y="3" width="7" height="7" rx="1" fill="white" />
                            <rect x="14" y="14" width="7" height="7" rx="1" fill="white" />
                        </svg>
                    </div>
                    <span>Tableau de bord</span>
                </div>
                <div class="nav-item" onclick="location.href='./users_dh.php'">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="white"/>
                        </svg>
                    </div>
                    <span>Gestion des utilisateurs</span>
                </div>
                <div class="nav-item" onclick="location.href='./statistique_boit.php'">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z" fill="white"/>
                        </svg>
                    </div>
                    <span>Gestion des dons</span>
                </div>
                <div class="nav-item" onclick="location.href='./ajoute_boite.php'">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7V10C2 16 6 20.5 12 22C18 20.5 22 16 22 10V7L12 2Z" fill="white"/>
                        </svg>
                    </div>
                    <span>Gestion des campagnes</span>
                </div>
            </nav>
        </aside>

    </div>

    <!-- Main Content -->
    <div class="main-content">
        <main class="page-content">
            <div class="content-container">
                <!-- Back Link -->
                <div style="margin-bottom: 20px;">
                    <a href="./dashboard_admin.php" style="color: #017960; text-decoration: none; font-weight: bold;">
                        ← Retour au tableau de bord admin
                    </a>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        Modifier: <?= htmlspecialchars($boite['nom']) ?>
                    </h1>
                    <button class="print-button" onclick="window.print()">
                        <svg class="nav-icon icon-stroke" viewBox="0 0 24 24" style="color: #828282;">
                            <polyline points="6,9 6,2 18,2 18,9" />
                            <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                            <rect x="6" y="14" width="12" height="8" />
                        </svg>
                    </button>
                </div>

                <!-- Admin Warning -->
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 20px 0; color: #92400e;">
                    <strong>⚠️ Mode Administrateur:</strong> Vous modifiez une boîte de don. Cette action sera enregistrée dans les logs d'administration.
                </div>

                <?php if ($message): ?>
                    <div style="padding: 15px; margin: 20px 0; border-radius: 8px; font-weight: bold; border: 1px solid; <?= $messageType === 'success' ? 'background-color: #d4edda; color: #155724; border-color: #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;' ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <!-- Box Stats -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px 0; color: #374151;">Statistiques de la boîte:</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong>Montant collecté:</strong> <?= number_format($boite['montant_collecte'] ?? 0, 2) ?> DH
                        </div>
                        <div>
                            <strong>Objectif:</strong> <?= number_format($boite['objectif_financier'], 2) ?> DH
                        </div>
                        <div>
                            <strong>Progression:</strong> 
                            <?php 
                            $progression = $boite['objectif_financier'] > 0 ? 
                                round(($boite['montant_collecte'] ?? 0) / $boite['objectif_financier'] * 100, 1) : 0;
                            echo $progression . '%';
                            ?>
                        </div>
                        <div>
                            <strong>Statut actuel:</strong> 
                            <span style="color: <?= $boite['statut'] === 'Active' ? '#059669' : '#dc2626' ?>;">
                                <?= htmlspecialchars($boite['statut']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <!-- Form -->
                <form class="form-container" method="POST" enctype="multipart/form-data" id="updateForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom de la Boîte <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="nom" name="nom" class="form-input" 
                               value="<?= htmlspecialchars($boite['nom']) ?>" 
                               required maxlength="100" minlength="3">
                    </div>

                    <div class="form-group">
                        <label for="objectif_financier" class="form-label">Objectif Financier (DH) <span style="color: #ef4444;">*</span></label>
                        <input type="number" id="objectif_financier" name="objectif_financier" class="form-input" 
                               value="<?= htmlspecialchars($boite['objectif_financier']) ?>" 
                               min="1" max="10000000" step="0.01" required>
                        <small style="color: #6b7280; font-size: 12px;">
                            Montant actuellement collecté: <?= number_format($boite['montant_collecte'] ?? 0, 2) ?> DH
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description de la Boîte <span style="color: #ef4444;">*</span></label>
                        <textarea id="description" name="description" class="form-input" 
                                  required maxlength="1000" minlength="10" 
                                  style="min-height: 120px; resize: vertical;"><?= htmlspecialchars($boite['description']) ?></textarea>
                        <small style="color: #6b7280; font-size: 12px;">
                            <span id="charCount">0</span>/1000 caractères
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">État de la Campagne</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="active" name="statut" value="Active" class="radio-input" 
                                       <?= $boite['statut'] === 'Active' ? 'checked' : '' ?>>
                                <label for="active" class="radio-label">En cours</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="terminee" name="statut" value="Terminée" class="radio-input" 
                                       <?= $boite['statut'] === 'Terminée' ? 'checked' : '' ?>>
                                <label for="terminee" class="radio-label">Terminée</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Changer l'image (optionnel)</label>
                        <div style="border: 2px dashed #cbd5e0; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer;" id="fileUploadArea">
                            <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;">
                            <p>Cliquez pour télécharger une nouvelle image</p>
                            <small style="color: #6b7280;">Formats: JPG, PNG, GIF, WebP. Max: 10 Mo.</small>
                        </div>
                        <div id="fileInfo" style="display: none; margin-top: 10px; padding: 10px; background: #f0fdf4; border-radius: 6px; color: #059669; font-size: 14px;"></div>
                    </div>

                    <button type="submit" class="submit-button" id="submitBtn">
                         MODIFIER 
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Character counter for description
        const descriptionTextarea = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        
        if (descriptionTextarea && charCount) {
            descriptionTextarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
            
            // Initialize character count
            charCount.textContent = descriptionTextarea.value.length;
        }

        // File upload functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const imageInput = document.getElementById('imageInput');
        const fileInfo = document.getElementById('fileInfo');

        if (fileUploadArea && imageInput) {
            fileUploadArea.addEventListener('click', function() {
                imageInput.click();
            });

            imageInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                } else {
                    resetFileUpload();
                }
            });
        }

        function handleFileSelect(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                alert('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
                resetFileUpload();
                return;
            }

            if (file.size > maxSize) {
                alert('La taille du fichier ne doit pas dépasser 10 Mo.');
                resetFileUpload();
                return;
            }

            // Show file info
            if (fileInfo) {
                fileInfo.innerHTML = `
                    <strong>✅ Nouvelle image sélectionnée:</strong> ${file.name}<br>
                    <strong>Taille:</strong> ${(file.size / 1024 / 1024).toFixed(2)} Mo
                `;
                fileInfo.style.display = 'block';
            }
        }

        function resetFileUpload() {
            if (imageInput) imageInput.value = '';
            if (fileInfo) fileInfo.style.display = 'none';
        }

        // Form validation with admin confirmation
        const updateForm = document.getElementById('updateForm');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                const nom = document.getElementById('nom').value.trim();
                const objectif = document.getElementById('objectif_financier').value;
                const description = document.getElementById('description').value.trim();

                if (!nom || nom.length < 3) {
                    e.preventDefault();
                    alert('Le nom de la boîte doit contenir au moins 3 caractères.');
                    return;
                }

                if (!objectif || parseFloat(objectif) <= 0) {
                    e.preventDefault();
                    alert('L\'objectif financier doit être supérieur à 0.');
                    return;
                }

                if (!description || description.length < 10) {
                    e.preventDefault();
                    alert('La description doit contenir au moins 10 caractères.');
                    return;
                }

                // Admin confirmation
                if (!confirm('Êtes-vous sûr de vouloir modifier cette boîte de don ? Cette action sera enregistrée dans les logs d\'administration.')) {
                    e.preventDefault();
                    return;
                }

                // Disable submit button to prevent double submission
                const submitBtn = document.getElementById('submitBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = '🔒 MODIFICATION EN COURS...';
                }
            });
        }

        // Auto-resize textarea
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        }
    </script>
</body>
</html>










