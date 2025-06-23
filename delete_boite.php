<?php
session_start();
include_once "./config/db.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_POST['id_boite'])) {
    header("Location: ./login.php");
    exit();
}

$id_boite = intval($_POST['id_boite']);

try {
    // Suppression de la boîte
    $stmt = $pdo->prepare("DELETE FROM boites WHERE id_boite = ?");
    $stmt->execute([$id_boite]);

    // Redirection après suppression
    $_SESSION['message'] = "La boîte a été supprimée avec succès.";
    header("Location: ./statistique_boit.php");
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    header("Location: ./statistique_boit.php");
    exit();
}
?>
