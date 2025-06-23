<?php
session_start();
include_once "./config/db.php";

// Check if user is admin (you may need to add an admin role system)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Administrateur') {
    header("Location: admin.php"); // Rediriger vers la page de connexion
    exit();
}
// Get statistics from database
try {
    // Get total users
    $usersStmt = $pdo->query("SELECT COUNT(*) as total_users FROM utilisateurs");
    $totalUsers = $usersStmt->fetch()['total_users'];

    // Get total donations
    $donationsStmt = $pdo->query("SELECT COUNT(*) as total_donations FROM parrainages");
    $totalDonations = $donationsStmt->fetch()['total_donations'];

    // Get total amount collected
    $amountStmt = $pdo->query("SELECT SUM(montant) as total_amount FROM parrainages");
    $totalAmount = $amountStmt->fetch()['total_amount'] ?? 0;

    // Get donation boxes with progress
    $boxesStmt = $pdo->query("SELECT * FROM boites ORDER BY nom ASC");
    $boxes = $boxesStmt->fetchAll();

    // Get recent users (last 5)
    $recentUsersStmt = $pdo->query("SELECT nom, prenom, email, id_utilisateur FROM utilisateurs ORDER BY id_utilisateur DESC LIMIT 5");
    $recentUsers = $recentUsersStmt->fetchAll();

    // Get recent donations (last 5)
    $recentDonationsStmt = $pdo->query("
        SELECT p.montant, p.nom, p.prenom, b.nom as boite_nom, p.id_parrainage 
        FROM parrainages p 
        JOIN boites b ON p.id_boite = b.id_boite 
        ORDER BY p.id_parrainage DESC 
        LIMIT 5
    ");
    $recentDonations = $recentDonationsStmt->fetchAll();

    // Get messages (if messages table has data)
    $messagesStmt = $pdo->query("
        SELECT m.contenu, m.date_envoi, u.nom, u.prenom 
        FROM messages m 
        JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur 
        ORDER BY m.date_envoi DESC 
        LIMIT 5
    ");
    $messages = $messagesStmt->fetchAll();

    // Calculate trends (compare with previous month)
    $currentMonth = date('Y-m');
    $previousMonth = date('Y-m', strtotime('-1 month'));

    $currentMonthDonations = $pdo->prepare("SELECT COUNT(*) as count FROM parrainages WHERE DATE_FORMAT(NOW(), '%Y-%m') = ?");
    $currentMonthDonations->execute([$currentMonth]);
    $currentCount = $currentMonthDonations->fetch()['count'];

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalUsers = $totalDonations = $totalAmount = 0;
    $boxes = $recentUsers = $recentDonations = $messages = [];
}

// Function to calculate percentage
function calculatePercentage($collected, $objective) {
    if ($objective == 0) return 0;
    return min(($collected / $objective) * 100, 100);
}

// Function to format currency
function formatCurrency($amount) {
    return number_format($amount, 2) . ' DH';
}
?>
<?php
     // Récupérer les 2 messages les plus récents avec infos utilisateur
    $stmt = $pdo->query("
    SELECT m.contenu, m.date_envoi, u.prenom, u.nom
    FROM messages m
    JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
    ORDER BY m.date_envoi DESC
    LIMIT 4
    ");
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Aide et Secours - Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/dachbord_admin.css">
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="search-container">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" stroke="#9CA3AF" stroke-width="2"/>
                        <path d="m21 21-4.35-4.35" stroke="#9CA3AF" stroke-width="2"/>
                    </svg>
                    <input type="text" placeholder="Rechercher..." class="search-input">
                    <a href="./logout.php" class="logout-btn">Déconnexion</a>

                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="welcome-section">
                    <h1>Bonjour, Admin</h1>
                    <p>Voici ce qui est arrivé à association au cours du mois dernier</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">
                                <span><?= $totalUsers ?></span>
                                <svg class="trend-up" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M7 14L12 9L17 14H7Z" fill="#017960"/>
                                </svg>
                            </div>
                            <p class="stat-label">Utilisateurs inscrits</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon donations-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M20 4H4C2.89 4 2.01 4.89 2.01 6L2 18C2 19.11 2.89 20 4 20H20C21.11 20 22 19.11 22 18V6C22 4.89 21.11 4 20 4ZM20 18H4V12H20V18ZM20 8H4V6H20V8Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">
                                <span><?= $totalDonations ?></span>
                                <svg class="trend-up" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M7 14L12 9L17 14H7Z" fill="#017960"/>
                                </svg>
                            </div>
                            <p class="stat-label">Total des dons</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon money-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12.88 17.76V19H11.12V17.73C10.14 17.56 9.25 17.11 8.7 16.37L9.98 15.09C10.44 15.7 11.13 16.06 12 16.06C12.88 16.06 13.56 15.58 13.56 14.83C13.56 14.04 12.9 13.7 11.68 13.28C10.18 12.78 8.88 12.24 8.88 10.5C8.88 9.11 9.9 8.06 11.12 7.73V6.5H12.88V7.75C13.75 7.94 14.43 8.45 14.91 9.13L13.65 10.33C13.29 9.8 12.73 9.5 12 9.5C11.2 9.5 10.56 9.95 10.56 10.5C10.56 11.17 11.28 11.5 12.63 11.97C14.13 12.47 15.31 13.13 15.31 14.83C15.31 16.24 14.25 17.42 12.88 17.76Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">
                                <span><?= formatCurrency($totalAmount) ?></span>
                                <svg class="trend-up" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M7 14L12 9L17 14H7Z" fill="#017960"/>
                                </svg>
                            </div>
                            <p class="stat-label">Montant Collecté</p>
                        </div>
                    </div>
                </div>

                <!-- Middle Section -->
                <div class="middle-grid">
                    <!-- Campaign Progress Cards -->
                    <?php 
                    $displayedBoxes = 0;
                    foreach ($boxes as $box): 
                        if ($displayedBoxes >= 2) break;
                        $percentage = calculatePercentage($box['montant_collecte'], $box['objectif_financier']);
                        $remaining = $box['objectif_financier'] - $box['montant_collecte'];
                        $strokeDashoffset = 452.389 - (452.389 * $percentage / 100);
                        $displayedBoxes++;
                    ?>
                    <div class="progress-card">
                        <h2><?= strtoupper(htmlspecialchars($box['nom'])) ?></h2>
                        <div class="progress-container">
                            <div class="circular-progress">
                                <svg width="160" height="160" viewBox="0 0 160 160">
                                    <circle cx="80" cy="80" r="72" fill="transparent" stroke="#e0e0e0" stroke-width="16"/>
                                    <circle cx="80" cy="80" r="72" fill="transparent" stroke="#017960" stroke-width="16" 
                                            stroke-dasharray="452.389" stroke-dashoffset="<?= $strokeDashoffset ?>" stroke-linecap="round" 
                                            transform="rotate(-90 80 80)" class="progress-circle"/>
                                </svg>
                                <div class="progress-text"><?= number_format($percentage, 0) ?>%</div>
                            </div>
                        </div>
                        <div class="progress-stats">
                            <div class="progress-stat">
                                <div class="stat-circle remaining"><?= number_format($remaining, 0) ?></div>
                                <p>RESTANT (DH)</p>
                            </div>
                            <div class="progress-stat">
                                <div class="stat-circle donation"><?= number_format($box['montant_collecte'], 0) ?></div>
                                <p>COLLECTÉ (DH)</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
            <!--  derniers messages-->
                <div class="messages-card">
                    <h2>MESSAGES RÉCENTS</h2>
                    <hr>
                    <div class="messages-list">
                        <?php if (empty($messages)): ?>
                            <div class="empty-state">Aucun message pour le moment</div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                            <div class="message-item">
                                <div class="message-avatar" style="background-color: #72c8cc;">
                                    <?= strtoupper(substr($message['prenom'] ?: $message['nom'], 0, 1)) ?>
                                </div>
                                <div class="message-content">
                                    <p class="message-name"><?= htmlspecialchars($message['prenom'] . ' ' . $message['nom']) ?></p>
                                    <p class="message-text"><?= htmlspecialchars(substr($message['contenu'], 0, 30)) ?>...</p>
                                </div>
                                <div class="message-meta">
                                    <span class="message-time"><?= date('M j, g:ia', strtotime($message['date_envoi'])) ?></span>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M9 18L15 12L9 6" stroke="#9CA3AF" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                </div>

                <!-- Bottom Section -->
                <div class="bottom-grid">
                    <div class="recent-card">
                        <h2>MEMBRES RÉCENTS</h2>
                        <hr>
                        <?php if (empty($recentUsers)): ?>
                            <div class="empty-state">Aucun nouveau membre</div>
                        <?php else: ?>
                            <?php foreach (array_slice($recentUsers, 0, 3) as $user): ?>
                            <div class="recent-item">
                                <div class="recent-avatar" style="background-color: #72c8cc;">
                                    <?= strtoupper(substr($user['prenom'] ?: $user['nom'], 0, 1)) ?>
                                </div>
                                <div class="recent-content">
                                    <p class="recent-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
                                    <p class="recent-id">ID: <?= $user['id_utilisateur'] ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="recent-card">
                        <h2>DONS RÉCENTS</h2>
                        <hr>
                        <?php if (empty($recentDonations)): ?>
                            <div class="empty-state">Aucun don récent</div>
                        <?php else: ?>
                            <?php foreach (array_slice($recentDonations, 0, 3) as $donation): ?>
                            <div class="recent-item">
                                <div class="recent-avatar" style="background-color: #8e4b6e;">
                                    <?= strtoupper(substr($donation['prenom'] ?: $donation['nom'], 0, 1)) ?>
                                </div>
                                <div class="recent-content">
                                    <p class="recent-name"><?= htmlspecialchars($donation['prenom'] . ' ' . $donation['nom']) ?></p>
                                    <p class="recent-id"><?= htmlspecialchars($donation['boite_nom']) ?></p>
                                </div>
                                <div class="recent-amount"><?= formatCurrency($donation['montant']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.querySelector('.search-input');
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    // Implement search functionality
                    console.log('Searching for:', this.value);
                }
            });

            // Animate progress circles on load
            const progressCircles = document.querySelectorAll('.progress-circle');
            progressCircles.forEach(circle => {
                const dashOffset = circle.style.strokeDashoffset;
                circle.style.strokeDashoffset = '452.389';
                setTimeout(() => {
                    circle.style.strokeDashoffset = dashOffset;
                }, 500);
            });
        });
    </script>
</body>
</html>