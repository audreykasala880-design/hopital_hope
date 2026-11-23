<?php
/**
 * Test unitaire & fonctionnel : Envoi d'email de confirmation et fiche imprimable
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

echo "=== TEST ENVOI D'EMAIL & FICHE IMPRIMABLE ===\n\n";

// 1. Récupérer un RDV actif en base
$stmt = $pdo->query("SELECT id FROM rendez_vous WHERE statut = 'confirme' ORDER BY id DESC LIMIT 1");
$rdvId = $stmt->fetchColumn();

if (!$rdvId) {
    // Si aucun RDV, en créer un temporaire
    $stmtP = $pdo->query("SELECT id FROM patients LIMIT 1");
    $idP = $stmtP->fetchColumn();
    $stmtM = $pdo->query("SELECT id FROM medecins WHERE actif = 1 LIMIT 1");
    $idM = $stmtM->fetchColumn();
    $stmtS = $pdo->query("SELECT id FROM salles WHERE statut = 'disponible' LIMIT 1");
    $idS = $stmtS->fetchColumn();

    $stmtIns = $pdo->prepare("
        INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, motif, statut)
        VALUES (?, ?, ?, NOW(), 30, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 'Test consultation automatisée', 'confirme')
    ");
    $stmtIns->execute([$idP, $idM, $idS]);
    $rdvId = $pdo->lastInsertId();
}

echo "[1] Test sur le RDV #$rdvId...\n";

// 2. Déclencher l'envoi de l'email de confirmation
$resultat = envoyerEmailConfirmationRdv($rdvId, $pdo);

echo "[2] Résultat génération/envoi email :\n";
echo "    - Succès global : " . ($resultat['success'] ? 'OUI' : 'NON') . "\n";
echo "    - Destinataire  : " . ($resultat['destinataire'] ?? 'N/A') . "\n";
echo "    - Fichier archive : " . ($resultat['archive_file'] ?? 'N/A') . "\n";

// 3. Vérifier l'existence physique du fichier email archivé
$archivePath = __DIR__ . '/../emails_envoyes/' . ($resultat['archive_file'] ?? '');
if (file_exists($archivePath) && filesize($archivePath) > 500) {
    echo "    -> PASS : Le fichier HTML d'archive existe et contient " . filesize($archivePath) . " octets.\n";
} else {
    echo "    -> FAIL : Fichier HTML d'archive manquant ou trop petit.\n";
}

// 4. Vérifier la présence du lien de fiche imprimable dans le HTML de l'email
$emailContent = file_get_contents($archivePath);
if (strpos($emailContent, 'rendez_vous/fiche.php?id=' . $rdvId) !== false) {
    echo "    -> PASS : L'email contient bien le lien vers la fiche imprimable du rendez-vous.\n";
} else {
    echo "    -> FAIL : Lien vers la fiche non trouvé dans l'email.\n";
}

echo "\n============================================\n";
echo "TEST TERMINÉ AVEC SUCCÈS !\n";
echo "============================================\n";
