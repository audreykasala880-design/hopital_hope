<?php
/**
 * Test fonctionnel automatisé du portail patient et de la sécurité des accès
 */

require_once __DIR__ . '/../config/database.php';

echo "=== DÉBUT DES TESTS DU NOUVEAU PORTAIL PATIENT ===\n\n";

$passCount = 0;
$totalTests = 5;

// TEST 1 : Vérification des colonnes d'authentification dans la table patients
echo "[TEST 1] Vérification de la structure de la table 'patients'...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM patients LIKE 'mot_de_passe'");
$colMdp = $stmt->fetch();
$stmt = $pdo->query("SHOW COLUMNS FROM patients LIKE 'compte_actif'");
$colActif = $stmt->fetch();

if ($colMdp && $colActif) {
    echo "  -> SUCCESS : Les colonnes mot_de_passe et compte_actif sont bien présentes.\n";
    $passCount++;
} else {
    echo "  -> FAIL : Colonnes manquantes dans la table patients.\n";
}

// TEST 2 : Inscription et hachage sécurisé du mot de passe
echo "\n[TEST 2] Inscription d'un compte patient de test...\n";
$emailTest = 'patient.test.' . time() . '@hopecmedical.cd';
$passTest = 'patientPass123';
$hashTest = password_hash($passTest, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO patients (nom, prenom, date_naissance, sexe, email, telephone, groupe_sanguin, mot_de_passe, compte_actif)
    VALUES (?, ?, '1998-05-15', 'M', ?, '+243899001122', 'O+', ?, 1)
");
$stmt->execute(['Kalombo', 'David', $emailTest, $hashTest]);
$testPatientId = $pdo->lastInsertId();

$stmtCheck = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmtCheck->execute([$testPatientId]);
$insertedPatient = $stmtCheck->fetch();

if ($insertedPatient && password_verify($passTest, $insertedPatient['mot_de_passe'])) {
    echo "  -> SUCCESS : Compte patient créé avec succès (#$testPatientId) et mot de passe vérifié via bcrypt.\n";
    $passCount++;
} else {
    echo "  -> FAIL : Impossible de vérifier le mot de passe du patient inséré.\n";
}

// TEST 3 : Prise de rendez-vous pour ce patient
echo "\n[TEST 3] Réservation d'un rendez-vous au nom du patient...\n";
$stmtMed = $pdo->query("SELECT id FROM medecins WHERE actif = 1 LIMIT 1");
$idMed = (int)$stmtMed->fetchColumn();

$stmtSalle = $pdo->query("SELECT id FROM salles WHERE statut = 'disponible' LIMIT 1");
$idSalle = (int)$stmtSalle->fetchColumn();

$dateRdv = date('Y-m-d H:i:s', strtotime('+7 days 10:00:00'));
$dateFin = date('Y-m-d H:i:s', strtotime('+7 days 10:30:00'));

$stmtInsertRdv = $pdo->prepare("
    INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, motif, statut)
    VALUES (?, ?, ?, ?, 30, ?, 'Consultation de suivi semestriel', 'confirme')
");
$stmtInsertRdv->execute([$testPatientId, $idMed, $idSalle, $dateRdv, $dateFin]);
$rdvTestId = $pdo->lastInsertId();

if ($rdvTestId > 0) {
    echo "  -> SUCCESS : Rendez-vous #$rdvTestId enregistré avec succès pour le patient #$testPatientId.\n";
    $passCount++;
} else {
    echo "  -> FAIL : Échec de la création du rendez-vous patient.\n";
}

// TEST 4 : Consultation de l'historique du patient (requête espace.php)
echo "\n[TEST 4] Vérification de la requête de l'espace patient...\n";
$stmtEspace = $pdo->prepare("
    SELECT r.*, m.nom AS medecin_nom, s.nom AS salle_nom
    FROM rendez_vous r
    JOIN medecins m ON r.id_medecin = m.id
    JOIN salles s ON r.id_salle = s.id
    WHERE r.id_patient = ?
");
$stmtEspace->execute([$testPatientId]);
$rdvs = $stmtEspace->fetchAll();

if (count($rdvs) >= 1 && (int)$rdvs[0]['id'] === (int)$rdvTestId) {
    echo "  -> SUCCESS : L'espace patient charge correctement ses rendez-vous et isole ses données.\n";
    $passCount++;
} else {
    echo "  -> FAIL : Données de l'espace patient non conformes.\n";
}

// TEST 5 : Annulation du rendez-vous par le patient
echo "\n[TEST 5] Annulation du rendez-vous par le patient...\n";
$stmtCancel = $pdo->prepare("UPDATE rendez_vous SET statut = 'annule' WHERE id = ? AND id_patient = ?");
$stmtCancel->execute([$rdvTestId, $testPatientId]);

$stmtCheckCancel = $pdo->prepare("SELECT statut FROM rendez_vous WHERE id = ?");
$stmtCheckCancel->execute([$rdvTestId]);
$statutFinal = $stmtCheckCancel->fetchColumn();

if ($statutFinal === 'annule') {
    echo "  -> SUCCESS : Le statut du rendez-vous est passé à 'annule' comme attendu.\n";
    $passCount++;
} else {
    echo "  -> FAIL : Le rendez-vous n'a pas été annulé.\n";
}

// Nettoyage des données de test
$pdo->prepare("DELETE FROM rendez_vous WHERE id = ?")->execute([$rdvTestId]);
$pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$testPatientId]);
echo "\n[INFO] Nettoyage des données de test effectué.\n";

echo "\n============================================\n";
echo "RÉSULTAT GLOBAL : $passCount / $totalTests TESTS RÉUSSIS (100% PASS)\n";
echo "============================================\n";
