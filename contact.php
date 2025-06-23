<?php
// Début du PHP en haut de la page pour gérer le POST

// Connexion à la base maraim
$host = 'localhost';   // adapte selon ton hébergement
$dbname = 'maraim';
$user = 'root';        // adapte
$pass = '';            // adapte

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur de connexion à la base : ' . $e->getMessage());
}

$messageSucces = '';
$messageErreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et sécuriser les données POST
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $nom = trim($_POST['nom']);
    $telephone = trim($_POST['telephone']);
    $contenu = trim($_POST['message']);

    if (!$email || empty($nom) || empty($telephone) || empty($contenu)) {
        $messageErreur = "Veuillez remplir tous les champs correctement.";
    } else {
        try {
            // 1. Vérifier si l'utilisateur existe déjà (par email)
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $id_utilisateur = $user['id_utilisateur'];
            } else {
                // 2. Insérer nouvel utilisateur
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, telephone) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $email, $telephone]);
                $id_utilisateur = $pdo->lastInsertId();
            }

            // 3. Insérer le message
            $stmt = $pdo->prepare("INSERT INTO messages (contenu, date_envoi, id_utilisateur) VALUES (?, NOW(), ?)");
            $stmt->execute([$contenu, $id_utilisateur]);

            $messageSucces = "Votre message a été envoyé avec succès. Nous vous contacterons bientôt.";
        } catch (Exception $e) {
            $messageErreur = "Erreur lors de l'envoi du message : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Association Aide et Secours - Contactez-nous</title>
    <link rel="stylesheet" href="./css/contact.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo-section">
                    <div class="logo-icon">
                        <svg viewBox="0 0 40 40" class="logo-svg-small">
                            <circle cx="20" cy="20" r="18" fill="#059669"/>
                            <path d="M12 25 Q12 20 16 20 Q20 20 20 25 L20 30 Q20 32 18 32 L14 32 Q12 32 12 30 Z" fill="white"/>
                            <path d="M20 25 Q20 20 24 20 Q28 20 28 25 L28 30 Q28 32 26 32 L22 32 Q20 32 20 30 Z" fill="white"/>
                            <path d="M20 12 L20 25 M18 14 L22 14 M18 16 L22 16 M18 18 L22 18" stroke="white" stroke-width="1" fill="none"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <div class="logo-arabic">جمعية المعون والإغاثة</div>
                        <div class="logo-french">Association Aide et Secours</div>
                    </div>
                </div>
                <nav class="nav-menu">
                    <a href="./index.php" class="nav-link">Accueil</a>
                    <a href="#" class="nav-link">À propos de nous</a>
                    <a href="./reveir.php" class="nav-link">Donation</a>
                    <a href="#" class="nav-link">Galerie</a>
                    <a href="./contact.php" class="nav-link active">Contact</a>
                </nav>
                <div class="nav-right">
                    <i class="fas fa-shopping-cart cart-icon"></i>
                    <button class="btn btn-connection">Connexion</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="contact-hero">
        <div class="container">
            <h1 class="hero-title">Contactez-nous</h1>
            <p class="hero-subtitle">Contactez-nous pour plus d'informations ou des solutions personnalisées</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-container">
                <!-- Contact Info -->
                <div class="contact-info">
                    <h2 class="info-title">Obtenir des Informations Maintenant</h2>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="info-text">+212 539 31 85 00</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-text">aidcom@gmail.com</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-text">Complexe Association aide et de secours - Branes 2, Tanger</div>
                    </div>
                    
                    <div class="social-media">
                        <h3 class="social-title">Social Media :</h3>
                        <div class="social-icons">
                            <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form-container">
                    <h2 class="form-title">Entrer en Contact !!</h2>
                    
                    <?php if ($messageSucces): ?>
                        <div style="color: green; margin-bottom: 15px; font-weight: bold;"><?= htmlspecialchars($messageSucces) ?></div>
                    <?php elseif ($messageErreur): ?>
                        <div style="color: red; margin-bottom: 15px; font-weight: bold;"><?= htmlspecialchars($messageErreur) ?></div>
                    <?php endif; ?>

                    <form class="contact-form" id="contactForm" method="post" action="">
                        <div class="form-group">
                            <input type="email" name="email" class="form-input" placeholder="Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <input type="text" name="nom" class="form-input" placeholder="Nom" required value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <input type="tel" name="telephone" class="form-input" placeholder="Numéro de Téléphone" required value="<?= isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <textarea name="message" class="form-textarea" placeholder="Message" rows="5" required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit">Envoyer</button>
                    </form>
                </div>
            </div>
            
            <!-- Map Section -->
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12950.019041307334!2d-5.8134!3d35.7595!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd0b875cf04c132d%3A0x76bfc571bfb4e747!2sBranes%2C%20Tangier%2C%20Morocco!5e0!3m2!1sen!2sus!4v1621234567890!5m2!1sen!2sus" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy"
                    title="Association Aide et Secours location"
                ></iframe>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
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
</body>
</html>
