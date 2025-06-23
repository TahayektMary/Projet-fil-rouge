<?php
session_start();
include_once "./config/db.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: ./login.php");
    exit(); // Arrêter l'exécution du script
}

// test login du user 
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
// variable de serche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

//apres search affichage des resultat d'pres querry 
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM boites WHERE nom LIKE ? OR description LIKE ? ORDER BY nom ASC");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->query("SELECT * FROM boites ORDER BY nom ASC");
}

$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul total donations
$totalStmt = $pdo->query("SELECT SUM(montant_collecte) as total_collected, SUM(objectif_financier) as total_objective FROM boites");
$totals = $totalStmt->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Aide et Secours - Boîtes de Parrainage</title>
    <link rel="stylesheet" href="./css/reveir.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>


        .hero-banner {
    background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.8),#017960), url('./images/59881.jpg'); /* Dégradé semi-transparent avec l'image */
    background-size: cover;
    background-position: center;
    height: 70vh; /* Ajustez la hauteur selon vos besoins */
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
}

        .hero-banner .container {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero-banner p {
            font-size: 1.25rem;
            margin: 0;
        }


        .user-menu {
            position: relative;
            display: inline-block;
        }
        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
        }
        .user-dropdown.show {
            display: block;
        }
        .user-dropdown a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #eee;
        }
        .user-dropdown a:hover {
            background-color: #f5f5f5;
        }
        .user-dropdown a:last-child {
            border-bottom: none;
        }
        .user-info {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #059669;
        }
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 200px;
        }
        .search-btn {
            padding: 8px 12px;
            background: #059669;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .search-btn:hover {
            background: #047857;
        }
        .totals-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .total-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .total-amount {
            font-size: 1.5em;
            font-weight: bold;
            color: #059669;
        }
        .total-label {
            color: #666;
            margin-top: 5px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .clear-search {
            margin-left: 10px;
            padding: 8px 12px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .clear-search:hover {
            background: #5a6268;
        }

        .box-frame {
            position: relative;
            width: 300px; /* Ajustez la largeur selon vos besoins */
            height: 200px; /* Ajustez la hauteur selon vos besoins */
            border: 2px solid #017960; /* Optionnel : ajouter une bordure */
            border-radius: 10px; /* Bordures arrondies */
            overflow: hidden; /* Assurez-vous que le contenu ne déborde pas */
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f4f4; /* Couleur de fond par défaut */
            z-index: 0;
        }

        .box-image {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ajuste l'image sans la déformer */
            position: absolute;
            top: 0;
            left: 0;

        }
        .logo-section img{
            width: 50%;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo-section">
                    <img src="./images/logoaide30-1.png" alt="" srcset="">
                </div>
                <nav class="nav-menu">
                    <a href="./index.php" class="nav-link">Accueil</a>
                    <a href="#a propos" class="nav-link">À propos de nous</a>
                    <a href="./reveir.php" class="nav-link active">Boites</a>
                    <a href="formileur.php" class="nav-link">Parrainer</a>
                    <a href="./contact.php" class="nav-link">Contact</a>
                </nav>
                <div class="nav-right">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-menu">
                            <button class="btn btn-connection" onclick="toggleUserMenu()">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($userName) ?>
                            </button>
                            <div class="user-dropdown" id="userDropdown">
                                <div class="user-info">Bonjour, <?= htmlspecialchars($userName) ?></div>
                                <a href="./dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                                <a href="./profile.php"><i class="fas fa-user-edit"></i> Mon profil</a>
                                <a href="./my-donations.php"><i class="fas fa-heart"></i> Mes dons</a>
                                <a href="./logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="./login.php"><button class="btn btn-connection">Connexion</button></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="container">
            <img src="" alt="" srcset="">
            <h1 class="hero-title">Boîtes de Parrainage</h1>
            <p>Découvrez toutes nos campagnes de dons et soutenez les causes qui vous tiennent à cœur</p>
        </div>
    </section>

    <!-- Search Section -->
    <section class="donation-tabs">
        <div class="container">
            <div class="tabs">
                <form method="GET" action="./reveir.php" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher une campagne..." 
                           class="search-input" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="./reveir.php" class="clear-search">Effacer</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>

    <!-- Totals Section statistiques -->
    <section class="totals-section">
        <div class="container">
            <h2>Statistiques des dons</h2>
            <div class="totals-grid">
                <div class="total-item">
                    <div class="total-amount"><?= number_format($totals['total_collected'], 2) ?> DH</div>
                    <div class="total-label">Total collecté</div>
                </div>
                <div class="total-item">
                    <div class="total-amount"><?= number_format($totals['total_objective'], 2) ?> DH</div>
                    <div class="total-label">Objectif total</div>
                </div>
                <div class="total-item">
                    <div class="total-amount"><?= count($donations) ?></div>
                    <div class="total-label">Campagnes actives</div>
                </div>
                <div class="total-item">
                    <?php 
                    $overallPercentage = $totals['total_objective'] > 0 ? 
                        ($totals['total_collected'] / $totals['total_objective']) * 100 : 0;
                    ?>
                    <div class="total-amount"><?= number_format($overallPercentage, 1) ?>%</div>
                    <div class="total-label">Progression globale</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Boites de donations -->
    <section class="sponsorship-boxes">
        <div class="container">
            <?php if (empty($donations)): ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Aucune campagne trouvée</h3>
                    <p>Essayez avec d'autres mots-clés ou <a href="./reveir.php">voir toutes les campagnes</a></p>
                </div>
            <?php else: ?>
                <div class="boxes-grid">
                    <?php foreach ($donations as $don): ?>
                        <div class="box-card">
                            <div class="box-header">
                               <div class="box-frame">
                                <?php 
                                    // Vérification de l'existence de l'image avec chemin absolu
                                    $imagePath = $don['image_path'] ?? '';
                                    $fullImagePath = __DIR__ . $imagePath;
                                ?>
                                <?php if (!empty($imagePath) && file_exists($fullImagePath)): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($don['nom']) ?>" class="box-image" />
                                <?php else: ?>
                                    <img src="./images/boite_Ekram.jpg" alt="Image par défaut" class="box-image" />
                                <?php endif; ?>
                            </div>
                                <?php 
                                    $percentage = ($don['montant_collecte'] / $don['objectif_financier']) * 100;

                                 if ($percentage == 100): ?>
                                    <div class="box-tag" style="background:rgb(16, 101, 47); color: #fff;">Terminer</div>
                                <?php elseif ($percentage >= 1 && $percentage <= 99): ?>
                                    <div class="box-tag" style="background: #ffc107; color: #000;">En cours</div>
                                <?php else: ?>
                                    <div class="box-tag" style="background: #f0a551; color: #000;">Nouveau</div>
                                <?php endif; ?>
                                
                                
                                <h3 class="box-title"><?= htmlspecialchars($don['nom']) ?></h3>
                                <p class="box-description"><?= htmlspecialchars(substr($don['description'], 0, 100)) ?>...</p>
                            </div>
                            <div class="box-content">
                                <div class="price-info">
                                    <div class="price">
                                        <span class="amount"><?= number_format($don['objectif_financier'], 2) ?> DH</span>
                                        <span class="period">objectif</span>
                                    </div>
                                    <div class="beneficiaries">
                                        <span>Montant collecté</span>
                                        <span class="count"><?= number_format($don['montant_collecte'], 2) ?> DH</span>
                                    </div>
                                </div>
                                <div class="progress-bar" style="background: #e9ecef; height: 8px; border-radius: 4px; margin: 10px 0;">
                                    <div class="progress-fill" style="width: <?= min($percentage, 100) ?>%; height: 100%; background: #059669; border-radius: 4px;"></div>
                                </div>
                                <div class="progress-text" style="text-align: center; font-size: 0.9em; color: #666;">
                                    <?= number_format($percentage, 1) ?>% atteint
                                </div>
                                <?php 
                        $percentage = ($don['montant_collecte'] / $don['objectif_financier']) * 100;
                        if ($percentage >= 100): ?>
                            <button class="btn btn-donate" disabled>Objectif atteint !</button>
                        <?php else: ?>
                            <a href="formileur.php?id=<?= $don['id_boite'] ?>">
                                <button class="btn btn-donate">Faire un don maintenant</button>
                            </a>
                        <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="a propos">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">Aperçu</h3>
                    <p class="footer-text">
                        Association Aide et Secours, association marocaine à caractère caritatif et social, de développement et d'utilité publique nationale, créée en vertu du dahir du 15 novembre 1958, bénéficie du statut consultatif spécial auprès du Conseil économique et social des Nations Unies depuis 2015, en 2017. Elle est spécialisée dans la conception, l'ingénierie et la mise en œuvre de services de soins intégrés fournis aux familles d'orphelins et de veuves.
                    </p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">Contactez-nous</h3>
                    <div class="contact-info">
                        <p>Phone: +212 539 31 85 00</p>
                        <p>Mobile: +212 661 43 67 22</p>
                    </div>
                    <div class="social-section">
                        <h4 class="social-title">Suivez-nous sur les réseaux sociaux</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon youtube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>Tous droits réservés à l'Association d'Aide et de Secours 2025©</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.btn-connection')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>
</body>
</html>