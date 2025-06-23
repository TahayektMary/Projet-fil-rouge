<?php
session_start();
include_once "./config/db.php";
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $member_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($member_id) {
        try {
         
            $stmt = $pdo->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?");
            $stmt->execute([$member_id]);
            $member_info = $stmt->fetch();
            
            if ($member_info) {
                $deleteStmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
                $result = $deleteStmt->execute([$member_id]);
                
                if ($result) {
                    $_SESSION['success_message'] = "Membre " . $member_info['prenom'] . " " . $member_info['nom'] . " supprimé avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la suppression.";
                }
            } else {
                $_SESSION['error_message'] = "Membre introuvable.";
            }
        } catch (PDOException $e) {
            error_log("Delete member error: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur de base de données lors de la suppression.";
        }
    } else {
        $_SESSION['error_message'] = "ID membre invalide.";
    }
    
    header("Location: ./membres_db.php");
    exit();
}


$search = trim($_GET['search'] ?? '');


$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR numero_tel LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

try {
    
    $sql = "SELECT id_utilisateur, nom, prenom, email, numero_tel, sexe FROM utilisateurs $whereClause ORDER BY prenom, nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_membres,
            COUNT(CASE WHEN sexe IN ('M', 'Homme', 'HOMME', 'Male', 'MALE') THEN 1 END) as hommes,
            COUNT(CASE WHEN sexe IN ('F', 'Femme', 'FEMME', 'Female', 'FEMALE') THEN 1 END) as femmes
        FROM utilisateurs
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

   
    $genderCheckStmt = $pdo->query("SELECT DISTINCT sexe FROM utilisateurs WHERE sexe IS NOT NULL");
    $genderValues = $genderCheckStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $membres = [];
    $stats = ['total_membres' => 0, 'hommes' => 0, 'femmes' => 0];
    $genderValues = [];
}

try {
    $adminStmt = $pdo->prepare("SELECT nom, prenom FROM users WHERE id_user = ?");
    $adminStmt->execute([$_SESSION['user_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        $admin = ['nom' => 'Admin', 'prenom' => 'Système'];
    }
} catch (PDOException $e) {
    $admin = ['nom' => 'Admin', 'prenom' => 'Système'];
}

function getInitials($prenom, $nom) {
    $prenom = $prenom ?? '';
    $nom = $nom ?? '';
    return strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
}


function formatGender($sexe) {
    $sexe = strtoupper(trim($sexe ?? ''));
    switch ($sexe) {
        case 'M':
        case 'HOMME':
        case 'MALE':
            return 'Homme';
        case 'F':
        case 'FEMME':
        case 'FEMALE':
            return 'Femme';
        default:
            return 'Non spécifié';
    }
}


function getGenderClass($sexe) {
    $sexe = strtoupper(trim($sexe ?? ''));
    switch ($sexe) {
        case 'M':
        case 'HOMME':
        case 'MALE':
            return 'gender-male';
        case 'F':
        case 'FEMME':
        case 'FEMALE':
            return 'gender-female';
        default:
            return 'gender-unknown';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Membres - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/dacbord_users.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
         <aside class="sidebar">
            <div class="logo-container">
                <img src="./images/alaoun.png" alt="Association Aide et Secours" class="logo">
            </div>
            <hr>
            <nav class="nav-menu">
                <div class="nav-item active">
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
                    <span>Nouveau Boite</span>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <form method="GET" style="width: 100%; position: relative;">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Rechercher un membre..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <?php if (!empty($search)): ?>
                            <button type="button" class="clear-search" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="header-actions">
                    <a href="./logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Déconnexion
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total_membres'] ?></div>
                        <div class="stat-label">Total Membres</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['hommes'] ?></div>
                        <div class="stat-label">Hommes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['femmes'] ?></div>
                        <div class="stat-label">Femmes</div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="page-header">
                        <h1 class="page-title">Gestion des Membres</h1>
                        <div class="header-buttons">
                            <a href="./ajouter_membre.php" class="btn btn-primary" title="Ajouter un membre">
                                <i class="fas fa-plus"></i>
                                Ajouter
                            </a>
                        </div>
                    </div>

                    <!-- tableau users -->
                    <div class="table-container">   
                        <!-- filtre des users trouver au cours de la recherche -->
                        <?php if (empty($membres)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-friends"></i>
                                <h3>Aucun membre trouvé</h3>
                                <p>
                                    <?php if (!empty($search)): ?>
                                        Aucun membre ne correspond à votre recherche.
                                        <br><a href="./membres_db.php" style="color: #059669; text-decoration: none;">Voir tous les membres</a>
                                    <?php else: ?>
                                        Aucun membre enregistré pour le moment.
                                        <br><a href="./ajouter_membre.php" style="color: #059669; text-decoration: none;">Ajouter le premier membre</a>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <table class="members-table">
                                <thead class="table-header">
                                    <tr>
                                        <th>Nom du membre</th>
                                        <th>Numéro de téléphone</th>
                                        <th>Email</th>
                                        <th>Sexe</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($membres as $membre): ?>
                                        <tr class="table-row">
                                            <td class="table-cell">
                                                <div class="member-info">
                                                    <div class="avatar">
                                                        <?= getInitials($membre['prenom'], $membre['nom']) ?>
                                                    </div>
                                                    <span class="member-name">
                                                        <?= htmlspecialchars(($membre['prenom'] ?? '') . ' ' . ($membre['nom'] ?? '')) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="table-cell member-contact">
                                                <?= htmlspecialchars($membre['numero_tel'] ?? 'N/A') ?>
                                            </td>
                                            <td class="table-cell member-contact">
                                                <?= htmlspecialchars($membre['email'] ?? 'N/A') ?>
                                            </td>
                                            <td class="table-cell">
                                                <span class="gender-badge <?= getGenderClass($membre['sexe']) ?>">
                                                    <?= formatGender($membre['sexe']) ?>
                                                </span>
                                            </td>
                                            <td class="table-cell">
                                                <div class="action-buttons">
                                                    <a href="./edit_membre.php?id=<?= $membre['id_utilisateur'] ?>" class="action-btn edit" title="Modifier le membre">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="./delete_user.php?id=<?= $membre['id_utilisateur'] ?>" 
                                                    class="action-btn delete" 
                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');" 
                                                    title="Supprimer le membre">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>