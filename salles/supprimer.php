<?php
/**
 * ====================================================================
 * SUPPRESSION D'UNE SALLE
 * ====================================================================
 */

require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Identifiant de salle non valide.");
    header('Location: ' . BASE_URL . '/salles/index.php');
    exit;
}

try {
    // Vérifier si des rendez-vous sont planifiés dans cette salle
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE id_salle = ?");
    $stmt->execute([$id]);
    $nbRdv = $stmt->fetchColumn();

    if ($nbRdv > 0) {
        flashMessage('warning', "Impossible de supprimer cette salle car elle est liée à $nbRdv rendez-vous dans la base. Veuillez plutôt changer son statut en 'Fermée' ou 'En Maintenance'.");
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM salles WHERE id = ?");
        $stmtDel->execute([$id]);
        flashMessage('success', "La salle a été supprimée avec succès.");
    }
} catch (PDOException $e) {
    flashMessage('danger', "Erreur lors de la suppression : " . $e->getMessage());
}

header('Location: ' . BASE_URL . '/salles/index.php');
exit;
