






<?php
session_start();
include_once "./config/db.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit();
}

try {
    // Récupérer toutes les boîtes avec leurs statistiques
    $boxesStmt = $pdo->query("
        SELECT 
            b.*,
            COUNT(p.id_parrainage) as total_donations,
            COALESCE(SUM(p.montant), 0) as total_collected_from_donations
        FROM boites b 
        LEFT JOIN parrainages p ON b.id_boite = p.id_boite 
        GROUP BY b.id_boite 
        ORDER BY b.nom ASC
    ");
    $boxes = $boxesStmt->fetchAll();

    // Statistiques globales
    $totalCollectedStmt = $pdo->query("SELECT SUM(montant_collecte) as total FROM boites");
    $totalCollected = $totalCollectedStmt->fetch()['total'] ?? 0;

    $totalObjectiveStmt = $pdo->query("SELECT SUM(objectif_financier) as total FROM boites");
    $totalObjective = $totalObjectiveStmt->fetch()['total'] ?? 0;

    $totalBoxesStmt = $pdo->query("SELECT COUNT(*) as total FROM boites");
    $totalBoxes = $totalBoxesStmt->fetch()['total'] ?? 0;

    // Données hebdomadaires (5 dernières semaines)
    $weeklyData = [];
    for ($i = 4; $i >= 0; $i--) {
        $weekStart = date('Y-m-d', strtotime("-$i weeks monday"));
        $weekEnd = date('Y-m-d', strtotime("-$i weeks sunday"));

        $weekStmt = $pdo->prepare("
            SELECT COALESCE(SUM(montant), 0) as weekly_total
            FROM parrainages 
            WHERE DATE(NOW()) BETWEEN ? AND ?
        ");
        $weekStmt->execute([$weekStart, $weekEnd]);
        $result = $weekStmt->fetch();
        $weeklyData[] = [
            'week_start' => $weekStart,
            'weekly_total' => $result['weekly_total'] ?? 0
        ];
    }
} catch (PDOException $e) {
    error_log("Erreur statistiques : " . $e->getMessage());
    $boxes = [];
    $totalCollected = $totalObjective = $totalBoxes = 0;
    $weeklyData = [];
}

// Fonctions utilitaires
function calculatePercentage($collected, $objective) {
    if ($objective == 0) return 0;
    return min(($collected / $objective) * 100, 100);
}

function formatCurrency($amount) {
    return number_format($amount, 0, ',', ' ') . ' DH';
}

function getStrokeDashOffset($percentage) {
    $circumference = 2 * M_PI * 45; // rayon = 45
    return $circumference - ($circumference * $percentage / 100);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Boîtes de Don</title>
    <link rel="stylesheet" href="./css/statistique_boit.css">
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

        <!-- Contenu principal -->
        <div class="main-content">
            <header class="header">
                <div class="header-left">
                    <div class="search-container">
                        <span class="search-icon">🔍</span>
                        <input type="text" placeholder="Rechercher..." class="search-input">
                    </div>
                </div>
                <div class="header-right">
                    <a href="./logout.php" class="logout-btn">Déconnexion</a>
                </div>
            </header>

            <main class="content">
                <div class="content-header">
                    <div class="title-section">
                        <h1>STATISTIQUES DES BOÎTES</h1>
                        <p>Aperçu des performances de toutes les campagnes de dons</p>
                    </div>
                    <div class="action-buttons">
                        <a href="./ajoute_boite.php" class="action-btn" title="Ajouter une boîte">+</a>
                    </div>
                </div>

                <!-- Résumé -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="card-content">
                            <div class="card-left">
                                <div class="card-icon">💰</div>
                                <div class="card-info">
                                    <div class="amount"><?= formatCurrency($totalCollected) ?></div>
                                    <div class="label">Montant Total Collecté</div>
                                </div>
                            </div>
                            <div class="trend-arrow">↗️</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-content">
                            <div class="card-left">
                                <div class="card-icon">🎯</div>
                                <div class="card-info">
                                    <div class="amount"><?= formatCurrency($totalObjective) ?></div>
                                    <div class="label">Objectif Total</div>
                                </div>
                            </div>
                            <div class="trend-arrow">📈</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <div class="card-content">
                            <div class="card-left">
                                <div class="card-icon">📦</div>
                                <div class="card-info">
                                    <div class="amount"><?= $totalBoxes ?></div>
                                    <div class="label">Boîtes Actives</div>
                                </div>
                            </div>
                            <div class="trend-arrow">📊</div>
                        </div>
                    </div>
                </div>

                <!-- Graphique circulaire -->
                <div class="progress-charts">
                    <?php if (empty($boxes)): ?>
                        <div class="empty-state">
                            <h3>Aucune boîte de don trouvée</h3>
                            <p>Commencez par créer votre première campagne de dons</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($boxes as $box):
                            $percentage = calculatePercentage($box['montant_collecte'], $box['objectif_financier']);
                            $remaining = max(0, $box['objectif_financier'] - $box['montant_collecte']);
                            $strokeDashOffset = getStrokeDashOffset($percentage);
                        ?>
                        <div class="progress-card">
                            <h3><?= strtoupper(htmlspecialchars($box['nom'])) ?></h3>
                            <div class="circular-progress">
                                <svg class="progress-ring" width="120" height="120">
                                    <circle class="progress-ring-bg" cx="60" cy="60" r="45"></circle>
                                    <circle class="progress-ring-fill" cx="60" cy="60" r="45"
                                            style="stroke-dasharray: <?= 2 * M_PI * 45 ?>; stroke-dashoffset: <?= $strokeDashOffset ?>;"
                                            transform="rotate(-90 60 60)">
                                    </circle>
                                </svg>
                                <div class="progress-text"><?= number_format($percentage, 0) ?>%</div>
                            </div>
                            <div class="progress-stats">
                                <div class="stat">
                                    <div class="stat-value">
                                        <span class="stat-dot gray"></span>
                                        <span class="stat-number"><?= number_format($remaining, 0) ?></span>
                                    </div>
                                    <div class="stat-label">RESTANT (DH)</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value">
                                        <span class="stat-dot green"></span>
                                        <span class="stat-number"><?= number_format($box['montant_collecte'], 0) ?></span>
                                    </div>
                                    <div class="stat-label">COLLECTÉ (DH)</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Tableau détaillé -->
                <?php if (!empty($boxes)): ?>
                <div class="detailed-stats">
                    <h3>Détails par Boîte</h3>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom de la Boîte</th>
                                    <th style="text-align: right;">Objectif</th>
                                    <th style="text-align: right;">Collecté</th>
                                    <th style="text-align: right;">Progression</th>
                                    <th style="text-align: center;">Statut</th>
                                    <th style="text-align: center;">Dons</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($boxes as $box): 
                                    $percentage = calculatePercentage($box['montant_collecte'], $box['objectif_financier']);
                                ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($box['nom']) ?></td>
                                    <td style="text-align: right;"><?= formatCurrency($box['objectif_financier']) ?></td>
                                    <td style="text-align: right; color: #059669; font-weight: 600;">
                                        <?= formatCurrency($box['montant_collecte']) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <span style="color: <?= $percentage >= 75 ? '#059669' : ($percentage >= 50 ? '#f59e0b' : '#ef4444') ?>; font-weight: 600;">
                                            <?= number_format($percentage, 1) ?>%
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="status-badge <?= $box['statut'] === 'Terminée' ? 'status-completed' : 'status-active' ?>">
                                            <?= htmlspecialchars($box['statut']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 600;"><?= $box['total_donations'] ?></td>
                                    <td style="text-align: center;">
                                        <div class="action-buttons">
                                            <form method="POST" action="delete_boite.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette boîte ?');">
                                                <input type="hidden" name="id_boite" value="<?= $box['id_boite'] ?>">
                                                <button type="submit" class="action-btn btn-delete" title="Supprimer">
                                                    🗑️
                                                </button>
                                            </form>
                                            <a href="./updete_boite.php?id=<?= $box['id_boite'] ?>" class="action-btn btn-edit" title="Modifier">
                                                ✏️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // Animation du cercle de progression au chargement
        document.addEventListener('DOMContentLoaded', function () {
            const progressCircles = document.querySelectorAll('.progress-ring-fill');
            progressCircles.forEach(circle => {
                const finalOffset = circle.style.strokeDashoffset;
                const circumference = circle.style.strokeDasharray.split(',')[0];
                circle.style.strokeDashoffset = circumference;
                setTimeout(() => {
                    circle.style.strokeDashoffset = finalOffset;
                }, 500);
            });
        });

        // Recherche en temps réel
        document.querySelector('.search-input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                row.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>