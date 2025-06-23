<?php
session_start();
include_once "./config/db.php";

// Get donation boxes without category filtering
$stmt = $pdo->query("SELECT * FROM boites LIMIT 4");
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Association Aide et Secours - Accueil</title>
    <link rel="stylesheet" href="./css/index.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .logo-section img{
            width: 50%;
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo-container">
                <div class="logo">
                    <img src="./images/logoaide30-1.png" alt="">
                </div>
            </div>
                <nav class="nav-menu">
                    <a href="./index.php" class="nav-link active">Accueil</a>
                    <a href="#a propos" class="nav-link">À propos de nous</a>
                    <a href="./reveir.php" class="nav-link">Boites</a>
                    <a href="./formileur.php" class="nav-link">Parrainer</a>
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

    <!-- Hero Section -->
    <div class="banner">
    <img src="./images/m4.png" alt="Bannière" class="banner-image">
    <div class="content">
        <div class="text-content">
            <h1 class="main-title">Parrainer un orphelin au sein de sa famille</h1>
            <p class="description">
                Une somme d'argent mensuelle versée à un orphelin pour lui assurer les conditions minimales d'une vie décente. Bien que nous tenions à maintenir la cohésion familiale en
            </p>
            <button class="cta-button"><a href="reveir.php">Faites un don maintenant</a></button>
        </div>
    </div>
</div>


    <!-- Donation Boxes Section -->
    <section class="sponsorship-boxes">
        <div class="container">
            <?php if (!empty($search)): ?>
                <h2>Résultats de recherche pour "<?= htmlspecialchars($search) ?>" (<?= count($donations) ?> résultat<?= count($donations) > 1 ? 's' : '' ?>)</h2>
            <?php endif; ?>
            
            <?php if (empty($donations)): ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Aucune campagne trouvée</h3>
                    <p>Essayez avec d'autres mots-clés ou <a href="./reveir.php">voir toutes les campagnes</a></p>
                </div>
            <?php else: ?>
                <!-- box place  -->
                 <div class="boxes-grid">
                    <?php foreach ($donations as $don): ?>
                        <div class="box-card">
                            <div class="box-header">
                                <div class="box-frame">
                                    <img src="./images/boite_Ekram.jpg" alt="Image Box" class="box-image">
                                    
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
                                <a href="formileur.php?id=<?= $don['id_boite'] ?>">
                                    <button class="btn btn-donate">Faire un don maintenant</button>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn-consulte"><a href="./reveir.php">consulter plus</a></button>
                </div>
            <?php endif; ?>
        </div>
    </section>


    <!-- Mission Section -->
    <section class="mission">
        <div class="container">
            <div class="mission-content">
                <div class="mission-text">
                    <h2 class="mission-title">Soyez généreux avec vos dons</h2>
                    <p class="mission-description">
                       Une œuvre de charité continue qui produit des bénéfices durables et multiplie ses récompenses.
                    </p>
                </div>
                <div class="mission-illustration">
                    <img src="./images/hand-drawn-clothing-donation-illustration.png" alt="hand draw clothing" >
                </div>
                <button><a href="reveir.php">Parrainer maintennant !</a></button>
            </div>
        </div>
    </section>

    <!-- Beneficiaries Section -->
   <div class="tabarou3">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="main-titlee">Domaines de charité</h1>
            <p class="subtitle">Possibilités de donner dans divers domaines caritatifs</p>
        </div>

        <!-- Cards Grid -->
        <div class="cards-grid">
            <!-- Card 1: Parrainez un orphelin -->
            <div class="card">
                <div class="card-background" style="background-image: url('./images/img-1.jpg');"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="card-inner">
                        <div class="card-text">
                            <h3 class="card-title">Parrainez un orphelin</h3>
                            <p class="card-subtitle">Faciliter la vie des orphelins</p>
                        </div>
                        <div class="card-arrow">
                            <div class="arrow-circle">
                                <svg class="arrow-icon" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Campagne contre le froid -->
            <div class="card">
                <div class="card-background" style="background-image: url('./images/img-2.jpg');"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="card-inner">
                        <div class="card-text">
                            <h3 class="card-title">Campagne contre le froid</h3>
                            <p class="card-subtitle">Cœur chaleureux 2024-2025</p>
                        </div>
                        <div class="card-arrow">
                            <div class="arrow-circle">
                                <svg class="arrow-icon" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Fonds d'entrée à l'école -->
            <div class="card">
                <div class="card-background" style="background-image: url('./images/img-3.jpg');"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="card-inner">
                        <div class="card-text">
                            <h3 class="card-title">Fonds d'entrée à l'école</h3>
                        </div>
                        <div class="card-arrow">
                            <div class="arrow-circle">
                                <svg class="arrow-icon" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Sacrifice de l'Aïd pour les familles orphelines -->
            <div class="card">
                <div class="card-background" style="background-image: url('./images/img-4.jpg');"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="card-inner">
                        <div class="card-text">
                            <h3 class="card-title">Sacrifice de l'Aïd pour les familles orphelines</h3>
                        </div>
                        <div class="card-arrow">
                            <div class="arrow-circle">
                                <svg class="arrow-icon" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 5: Vêtements de l'Aïd pour les orphelins -->
            <div class="card">
                <div class="card-background" style="background-image: url('./images/img-5.jpg');"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="card-inner">
                        <div class="card-text">
                            <h3 class="card-title">Vêtements de l'Aïd pour les orphelins</h3>
                        </div>
                        <div class="card-arrow">
                            <div class="arrow-circle">
                                <svg class="arrow-icon" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 6: Panier du Ramadan -->
            <div class="card">
                <div class="card-background" style="background-image: url('./images/img-6.jpg');"></div>
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="card-inner">
                        <div class="card-text">
                            <h3 class="card-title">Panier du Ramadan</h3>
                        </div>
                        <div class="card-arrow">
                            <div class="arrow-circle">
                                <svg class="arrow-icon" viewBox="0 0 24 24">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <section class="statistics">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-number">1,500+</div>
                    <div class="stat-label">Enfants aidés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-number">800+</div>
                    <div class="stat-label">Familles soutenues</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-number">2,000+</div>
                    <div class="stat-label">Bourses d'études</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">5,000+</div>
                    <div class="stat-label">Bénéficiaires</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Children Contributions Section -->

<div class="gallery-container">
    <div class="gallery-wrapper">
        <!-- Main Title -->
        <h1 class="gallery-title">
            Les contributions créatives de nos enfants
        </h1>

        <!-- Artwork Grid -->
        <div class="gallery-grid">
            <!-- Card 1 -->
            <div class="gallery-item">
                <div class="image-wrapper">
                    <img src="./images/real-1.jpg" alt="">
                    <div class="image-hover"></div>
                    <div class="artist-details">
                        <h3 class="artist-name">Wasima Al-Jaidi,</h3>
                        <p class="artist-grade">Troisième Collège</p>
                    </div>
                </div>
                <div class="item-details">
                    <p class="item-description">
                        Ce tableau a été réalisé pendant la période de quarantaine résultant 
                        de l'apparition de la pandémie de Covid-19.
                    </p>
                    <button class="details-button" aria-label="View artwork details">
                        <svg class="details-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="gallery-item">
                <div class="image-wrapper">
                    <img src="./images/real-2.jpg" alt="">
                    <div class="image-hover"></div>
                    <div class="artist-details">
                        <h3 class="artist-name">Fatima Al-Washni</h3>
                        <p class="artist-grade">Première Collège</p>
                    </div>
                </div>
                <div class="item-details">
                    <p class="item-description">
                        Ce tableau a été réalisé pendant la période de quarantaine résultant 
                        de l'apparition de la pandémie de Covid-19.
                    </p>
                    <button class="details-button" aria-label="View artwork details">
                        <svg class="details-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="gallery-item">
                <div class="image-wrapper">
                    <img src="./images/real-3.jpg" alt="">
                    <div class="image-hover"></div>
                    <div class="artist-details">
                        <h3 class="artist-name">Oussama,</h3>
                        <p class="artist-grade">Tronc Commun</p>
                    </div>
                </div>
                <div class="item-details">
                    <p class="item-description">
                        Ce tableau a été réalisé pendant la période de quarantaine résultant 
                        de l'apparition de la pandémie de Covid-19.
                    </p>
                    <button class="details-button" aria-label="View artwork details">
                        <svg class="details-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- Mobile App Section -->
    <section class="hero-social-container">
        <div class="content-wrapper-principale">
            <!-- Contenu textuel à gauche -->
            <div class="texte-principal-zone">
                <h1 class="titre-reseaux-sociaux">Suivez-nous sur les réseaux sociaux</h1>
                <p class="sous-titre-description">
                    Pour un don permanent et une récompense ininterrompue
                </p>

                <!-- Icônes des réseaux sociaux -->
                <div class="icones-sociales-rangee">
                    <div class="bouton-reseau facebook-container">
                        <svg class="icone-sociale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                        </svg>
                    </div>
                    <div class="bouton-reseau instagram-container">
                        <svg class="icone-sociale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                            <path d="m16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                        </svg>
                    </div>
                    <div class="bouton-reseau twitter-container">
                        <svg class="icone-sociale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                        </svg>
                    </div>
                    <div class="bouton-reseau youtube-container">
                        <svg class="icone-sociale" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/>
                            <polygon points="9.75,15.02 15.5,11.75 9.75,8.48"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Image des appareils à droite -->
            <div class="zone-appareils-mockup">
                <img src="./images/social-media-removebg-preview.png" alt="Mockup des appareils" class="image-dispositifs-complets">
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer" id="a propos">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">Aperçu</h3>
                    <p class="footer-text">
                        Association Aide et Secours, association marocaine à caractère caritatif et social, 
                        de développement et d'utilité publique nationale, créée en vertu du dahir du 15 novembre 1958, 
                        bénéficie du statut consultatif spécial auprès du Conseil économique et social des Nations Unies 
                        depuis 2015, en 2017. Elle est spécialisée dans la conception, l'ingénierie et la mise en œuvre 
                        de services de soins intégrés fournis aux familles d'orphelins et de veuves.
                    </p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">Contactez-nous</h3>
                    <div class="contact-info">
                        <p><i class="fas fa-phone"></i> Phone: +212 539 31 85 00</p>
                        <p><i class="fas fa-mobile-alt"></i> Mobile: +212 661 43 67 22</p>
                        <p><i class="fas fa-envelope"></i> contact@aide-secours.ma</p>
                        <p><i class="fas fa-map-marker-alt"></i> Rabat, Maroc</p>
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