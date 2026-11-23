<?php
/**
 * ====================================================================
 * CHANGEMENT RAPIDE DE STATUT D'UN RENDEZ-VOUS
 * ====================================================================
 */

require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
$nouveauStatut = trim($_GET['statut'] ?? '');
$csrf = $_GET['csrf'] ?? '';

if (!verifyCsrfToken($csrf)) {
    flashMessage('danger', "Erreur de sécurité CSRF.");
    header('Location: ' . BASE_URL . '/rendez_vous/index.php');
    exit;
}

if (!$id || !in_array($nouveauStatut, ['confirme', 'termine', 'annule', 'en_attente', 'reporte'])) {
    flashMessage('danger', "Paramètres de requête non valides.");
    header('Location: ' . BASE_URL . '/rendez_vous/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveauStatut, $id]);

    $messages = [
        'termine' => "Le rendez-vous #$id a été marqué comme terminé.",
        'annule' => "Le rendez-vous #$id a été annulé. Le créneau et la salle ont été libérés.",
        'confirme' => "Le rendez-vous #$id est désormais confirmé.",
        'reporte' => "Le rendez-vous #$id a été marqué comme reporté."
    ];

    flashMessage('success', $messages[$nouveauStatut] ?? "Statut mis à jour.");
} catch (PDOException $e) {
    flashMessage('danger', "Erreur lors de la mise à jour : " . $e->getMessage());
}

header('Location: ' . BASE_URL . '/rendez_vous/index.php');
exit;
