<?php
session_start();
include_once "./config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

$message = "";
$messageType = "";
$nom = "";
$objectif_financier = "";
$description = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "⚠️ Token de sécurité invalide.";
        $messageType = 'error';
    } else {
        // Get and sanitize form data
        $nom = trim($_POST['nom'] ?? '');
        $objectif_financier = filter_var($_POST['objectif_financier'] ?? '', FILTER_VALIDATE_FLOAT);
        $description = trim($_POST['description'] ?? '');
        
        // Validation
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
        
        // Check if box name already exists
        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM boites WHERE nom = ?");
            $checkStmt->execute([$nom]);
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Une boîte avec ce nom existe déjà.";
            }
        } catch (PDOException $e) {
            error_log("Database check error: " . $e->getMessage());
            $errors[] = "Erreur lors de la vérification du nom.";
        }
        
        // Handle image upload
        $image_path = null;
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
                    $image_path = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                        $errors[] = "Erreur lors du téléchargement de l'image.";
                        $image_path = null;
                    }
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle other upload errors
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "Le fichier est trop volumineux.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "Le fichier n'a été que partiellement téléchargé.";
                    break;
                default:
                    $errors[] = "Erreur lors du téléchargement du fichier.";
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
                
                // Build the INSERT query based on available columns
                $insertColumns = ['nom', 'description', 'objectif_financier', 'montant_collecte', 'statut'];
                $insertValues = [$nom, $description, $objectif_financier, 0, 'Active'];
                $placeholders = ['?', '?', '?', '?', '?'];
                
                // Add image column if it exists
                if (in_array('image', $columns)) {
                    $insertColumns[] = 'image';
                    $insertValues[] = $image_path;
                    $placeholders[] = '?';
                } elseif (in_array('image_path', $columns)) {
                    $insertColumns[] = 'image_path';
                    $insertValues[] = $image_path;
                    $placeholders[] = '?';
                }
                
                // Build the SQL query
                $sql = "INSERT INTO boites (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                
                $result = $stmt->execute($insertValues);

                if ($result) {
                    $message = "✅ Boîte de don '" . htmlspecialchars($nom) . "' créée avec succès !";
                    $messageType = 'success';
                    
                    // Clear form data on success
                    $nom = $description = $objectif_financier = '';
                    
                    // Redirect after 3 seconds
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = './statistique_boit.php';
                        }, 3000);
                    </script>";
                } else {
                    $message = "❌ Erreur lors de la création de la boîte.";
                    $messageType = 'error';
                }
                
            } catch (PDOException $e) {
                error_log("Box creation error: " . $e->getMessage());
                
                // Clean up uploaded file if database insert fails
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                
                $message = "❌ Erreur lors de la création de la boîte: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Boîte - Association Aide et Secours</title>
    <link rel="stylesheet" href="./css/ajoute_boite.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <div class="logo">
                    <img src="./images/alaoun.png" alt="">
                </div>
            </div>
            
            <nav class="nav">
                <a href="./dachbord_admin.php" class="nav-item">
                    <div class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="7" height="7" rx="1" fill="white" />
                            <rect x="3" y="14" width="7" height="7" rx="1" fill="white" />
                            <rect x="14" y="3" width="7" height="7" rx="1" fill="white" />
                            <rect x="14" y="14" width="7" height="7" rx="1" fill="white" />
                        </svg>
                    </div>
                    <span>Tableau de bord</span>
                </a>
                
                <a href="./statistique_boites.php" class="nav-item active">
                    <div class="nav-icon">
                         <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z" fill="white"/>
                        </svg>
                    </div>
                    <span>Gestion des dons</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="search-container">
                    <div class="search-icon">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" stroke="#9CA3AF" stroke-width="2"/>
                        <path d="m21 21-4.35-4.35" stroke="#9CA3AF" stroke-width="2"/>
                    </svg>
                    </div>
                    <input type="text" class="search-input" placeholder="Rechercher...">
                </div>
                
                <div class="header-right">
                    <a href="./logout.php" style="background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                        Déconnexion
                    </a>
                </div>
            </header>

            <!-- Form Section -->
            <main class="form-section">
                <div style="margin-bottom: 20px;">
                    <a href="./statistique_boit.php" style="color: #059669; text-decoration: none; font-weight: bold;">
                        ← Retour aux statistiques
                    </a>
                </div>

                <div class="form-header">
                    <h1 class="form-title">Nouvelle Boîte de Don</h1>
                </div>

                <?php if ($message): ?>
                    <div style="padding: 15px; margin: 20px 0; border-radius: 8px; font-weight: bold; border: 1px solid; <?= $messageType === 'success' ? 'background-color: #d4edda; color: #155724; border-color: #c3e6cb;' : 'background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;' ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form class="form" method="POST" enctype="multipart/form-data" id="boxForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="input-group">
                        <label>Nom de la Boîte <span style="color: #ef4444;">*</span></label>
                        <input type="text" class="form-input" name="nom" 
                               value="<?= htmlspecialchars($nom) ?>" 
                               placeholder="Ex: Aide aux orphelins de Casablanca" 
                               required maxlength="100" minlength="3">
                    </div>

                    <div class="input-group">
                        <label>Objectif Financier (DH) <span style="color: #ef4444;">*</span></label>
                        <input type="number" class="form-input" name="objectif_financier" 
                               value="<?= htmlspecialchars($objectif_financier) ?>" 
                               placeholder="Ex: 50000" min="1" max="10000000" step="0.01" required>
                    </div>

                    <div class="input-group">
                        <label>Description de la Campagne <span style="color: #ef4444;">*</span></label>
                        <textarea class="form-input" name="description" 
                                  placeholder="Décrivez l'objectif de cette campagne de dons..." 
                                  required maxlength="1000" minlength="10" 
                                  style="min-height: 120px; resize: vertical;"><?= htmlspecialchars($description) ?></textarea>
                        <small style="color: #6b7280; font-size: 12px;">
                            <span id="charCount">0</span>/1000 caractères
                        </small>
                    </div>

                    <div class="file-upload-section">
                        <label>Image de la Campagne (optionnel)</label>
                        <p class="file-upload-text">
                            Joindre une image. La taille de votre fichier ne doit pas dépasser 10 Mo.
                            Formats acceptés: JPG, PNG, GIF, WebP.
                        </p>
                        
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="upload-icon">⬆️</div>
                            <p class="upload-text">Cliquez pour télécharger une image ou glissez-déposez</p>
                            <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;">
                        </div>
                        
                        <div id="fileInfo" style="display: none; margin-top: 15px; padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; color: #059669; font-size: 14px;"></div>
                    </div>

                    <button type="submit" class="submit-button" id="submitBtn">
                        AJOUTER LA BOÎTE
                    </button>
                </form>
            </main>
        </div>
    </div>

    <script>
        // Character counter for description
        const descriptionTextarea = document.querySelector('textarea[name="description"]');
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
        const submitBtn = document.getElementById('submitBtn');

        if (fileUploadArea && imageInput) {
            // Click to upload
            fileUploadArea.addEventListener('click', function() {
                imageInput.click();
            });

            // Handle file selection
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
                    <strong>✅ Fichier sélectionné:</strong> ${file.name}<br>
                    <strong>Taille:</strong> ${(file.size / 1024 / 1024).toFixed(2)} Mo<br>
                    <strong>Type:</strong> ${file.type}
                `;
                fileInfo.style.display = 'block';
            }

            // Update upload area text
            const uploadText = fileUploadArea.querySelector('.upload-text');
            if (uploadText) {
                uploadText.textContent = '✅ Image sélectionnée: ' + file.name;
            }
        }

        function resetFileUpload() {
            if (imageInput) imageInput.value = '';
            if (fileInfo) fileInfo.style.display = 'none';
            const uploadText = fileUploadArea.querySelector('.upload-text');
            if (uploadText) {
                uploadText.textContent = 'Cliquez pour télécharger une image ou glissez-déposez';
            }
        }

        // Form validation
        const boxForm = document.getElementById('boxForm');
        if (boxForm) {
            boxForm.addEventListener('submit', function(e) {
                const nom = document.querySelector('input[name="nom"]').value.trim();
                const objectif = document.querySelector('input[name="objectif_financier"]').value;
                const description = document.querySelector('textarea[name="description"]').value.trim();

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

                // Disable submit button to prevent double submission
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'CRÉATION EN COURS...';
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