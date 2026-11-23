<?php
/**
 * ====================================================================
 * SUPPRESSION D'UN MÉDECIN
 * ====================================================================
 */

require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Identifiant de médecin invalide.");
    header('Location: ' . BASE_URL . '/medecins/index.php');
    exit;
}

try {
    // Vérifier si le médecin a des rendez-vous rattachés
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE id_medecin = ?");
    $stmt->execute([$id]);
    $nbRdv = $stmt->fetchColumn();

    if ($nbRdv > 0) {
        flashMessage('warning', "Impossible de supprimer ce médecin car il possède $nbRdv rendez-vous dans l'historique médical. Pour préserver les données, vous pouvez plutôt désactiver son compte (Statut : Inactif).");
    } else {
        $stmtDelete = $pdo->prepare("DELETE FROM medecins WHERE id = ?");
        $stmtDelete->execute([$id]);
        flashMessage('success', "Le médecin a été supprimé avec succès.");
    }
} catch (PDOException $e) {
    flashMessage('danger', "Erreur lors de la suppression : " . $e->getMessage());
}

header('Location: ' . BASE_URL . '/medecins/index.php');
exit;
