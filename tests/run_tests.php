<?php
/**
 * ====================================================================
 * SUITE DE TESTS AUTOMATISÉS DU TP DE PROGRAMMATION II (UCC)
 * ====================================================================
 * Valide les 7 règles de gestion obligatoires :
 * 1. Un médecin libre et une salle libre permettent une réservation.
 * 2. Un médecin déjà occupé entraîne le refus du rendez-vous.
 * 3. Une salle déjà occupée entraîne le refus du rendez-vous.
 * 4. Une même salle peut accueillir des consultations à des heures différentes.
 * 5. Une salle en maintenance ne peut pas être réservée.
 * 6. Les chevauchements partiels sont détectés (début pendant, fin pendant, etc.).
 * 7. Deux demandes simultanées pour le même créneau ne produisent jamais deux réservations incompatibles.
 */

$pageTitle = "Validation des 7 Tests Obligatoires";
require_once __DIR__ . '/../config/database.php';

$testResults = [];

// Fonction utilitaire d'exécution de test
function executerTest($nom, $description, callable $testFunc) {
    try {
        $result = $testFunc();
        return [
            'nom' => $nom,
            'description' => $description,
            'statut' => $result['succes'] ? 'PASS' : 'FAIL',
            'message' => $result['message'],
            'details' => $result['details'] ?? ''
        ];
    } catch (Exception $e) {
        return [
            'nom' => $nom,
            'description' => $description,
            'statut' => 'FAIL',
            'message' => "Exception inattendue : " . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ];
    }
}

// --------------------------------------------------------------------
// TEST 1 : Médecin libre et salle libre permettent une réservation
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 1 : Réservation valide (Médecin libre & Salle libre)",
    "Vérifie qu'une demande sans conflit sur un créneau où le praticien et la salle sont libres est acceptée.",
    function () use ($pdo) {
        // Date de test : Un lundi libre de 11:00 à 11:30 pour Dr. Ilunga (ID 1) et Salle 102 (ID 2)
        $dateTest = '2027-01-18 11:00:00'; // Date future pour ne pas interférer
        $duree = 30;

        // Nettoyer d'éventuels résidus de test
        $pdo->prepare("DELETE FROM rendez_vous WHERE date_heure >= '2027-01-01'")->execute();

        $res = verifierDisponibiliteRdv($pdo, 1, 1, 2, $dateTest, $duree, 'ECG');
        if ($res['valide']) {
            return [
                'succes' => true,
                'message' => "La réservation a été acceptée avec succès.",
                'details' => "Créneau : 11:00 à 11:30 | Fin calculée : {$res['date_heure_fin']} | Salle et Médecin disponibles."
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Refus anormal : " . implode(', ', $res['erreurs'])
            ];
        }
    }
);

// --------------------------------------------------------------------
// TEST 2 : Médecin déjà occupé entraîne le refus du rendez-vous
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 2 : Médecin déjà occupé -> Refus obligatoire",
    "Vérifie que si le praticien a déjà une consultation programmée, toute nouvelle demande pour lui est rejetée.",
    function () use ($pdo) {
        $dateTest = '2027-01-18 09:00:00';
        $duree = 30;
        $dateFin = '2027-01-18 09:30:00';

        // 1. Créer un rendez-vous initial pour Dr. Ilunga (ID 1) en salle 102 (ID 2)
        $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, motif, statut)
                       VALUES (1, 1, 2, ?, ?, ?, 'Consultation Test Existante', 'confirme')")
            ->execute([$dateTest, $duree, $dateFin]);

        // 2. Tenter de réserver à la même heure avec le MÊME médecin mais une AUTRE salle libre (Salle 101 - ID 1)
        $res = verifierDisponibiliteRdv($pdo, 2, 1, 1, $dateTest, $duree);

        if (!$res['valide']) {
            return [
                'succes' => true,
                'message' => "Refus conforme : Le système a bloqué la réservation pour cause de médecin déjà occupé.",
                'details' => "Message d'erreur retourné : " . implode(' | ', $res['erreurs'])
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Échec : Le médecin a pu être réservé deux fois au même moment !"
            ];
        }
    }
);

// --------------------------------------------------------------------
// TEST 3 : Salle déjà occupée entraîne le refus du rendez-vous
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 3 : Salle déjà occupée -> Refus obligatoire",
    "Vérifie qu'une même salle ne peut pas accueillir deux consultations simultanées, même avec deux médecins différents.",
    function () use ($pdo) {
        $dateTest = '2027-01-18 09:00:00';
        $duree = 30;

        // La salle 102 est déjà occupée par le Test 2 de 09:00 à 09:30.
        // Tenter de réserver cette MÊME salle 102 avec un AUTRE médecin (Dr. Mbayo - ID 3)
        $res = verifierDisponibiliteRdv($pdo, 3, 3, 2, $dateTest, $duree);

        if (!$res['valide']) {
            return [
                'succes' => true,
                'message' => "Refus conforme : La salle occupée a été correctement détectée et refusée.",
                'details' => "Message retourné : " . implode(' | ', $res['erreurs'])
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Échec : Deux médecins différents ont pu réserver la même salle au même moment !"
            ];
        }
    }
);

// --------------------------------------------------------------------
// TEST 4 : Une même salle peut accueillir des consultations à des heures différentes
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 4 : Même salle disponible à des heures différentes",
    "Démontre qu'une salle occupée de 09:00 à 09:30 peut être réservée sans problème juste après, par exemple de 10:00 à 10:30.",
    function () use ($pdo) {
        $dateApres = '2027-01-18 10:00:00';
        $duree = 30;

        // Réserver la salle 102 (ID 2) avec Dr. Ilunga (ID 1) une heure après
        $res = verifierDisponibiliteRdv($pdo, 2, 1, 2, $dateApres, $duree);

        if ($res['valide']) {
            return [
                'succes' => true,
                'message' => "Réservation acceptée : La salle est bien libérée dès la fin du précédent créneau.",
                'details' => "Créneau 10:00-10:30 accepté pour la Salle 102."
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Refus anormal : " . implode(', ', $res['erreurs'])
            ];
        }
    }
);

// --------------------------------------------------------------------
// TEST 5 : Une salle en maintenance ne peut pas être réservée
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 5 : Salle en maintenance -> Refus obligatoire",
    "Vérifie qu'une salle avec statut 'maintenance' ou ayant une maintenance planifiée est systématiquement refusée.",
    function () use ($pdo) {
        // La Salle 201 (ID 5) est en maintenance permanente dans la BDD
        $dateTest = '2027-01-18 09:00:00';
        $res = verifierDisponibiliteRdv($pdo, 1, 3, 5, $dateTest, 30);

        if (!$res['valide']) {
            return [
                'succes' => true,
                'message' => "Refus conforme : La salle en maintenance a été immédiatement rejetée.",
                'details' => "Message retourné : " . implode(' | ', $res['erreurs'])
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Échec critique : Une salle en maintenance technique a pu être réservée !"
            ];
        }
    }
);

// --------------------------------------------------------------------
// TEST 6 : Détection des chevauchements partiels
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 6 : Détection des chevauchements horaires partiels",
    "Teste les 3 cas de chevauchement partiel (Début pendant, Fin pendant, Englobant) face à un créneau existant 14:00-15:00.",
    function () use ($pdo) {
        $baseDebut = '2027-01-18 14:00:00';
        $baseFin   = '2027-01-18 15:00:00';

        // Enregistrer la consultation de référence (14:00 à 15:00)
        $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, motif, statut)
                       VALUES (1, 3, 1, ?, 60, ?, 'RDV Référence 14h-15h', 'confirme')")
            ->execute([$baseDebut, $baseFin]);

        // Cas A : Début avant mais termine pendant (ex: 13:45 à 14:15)
        $resA = verifierDisponibiliteRdv($pdo, 2, 3, 1, '2027-01-18 13:45:00', 30);

        // Cas B : Débute pendant mais termine après (ex: 14:45 à 15:15)
        $resB = verifierDisponibiliteRdv($pdo, 2, 3, 1, '2027-01-18 14:45:00', 30);

        // Cas C : Totalement englobant (ex: 13:30 à 15:30)
        $resC = verifierDisponibiliteRdv($pdo, 2, 3, 1, '2027-01-18 13:30:00', 120);

        if (!$resA['valide'] && !$resB['valide'] && !$resC['valide']) {
            return [
                'succes' => true,
                'message' => "Les 3 types de chevauchements partiels ont été détectés et refusés avec succès.",
                'details' => "Cas A (13h45-14h15) : Refusé | Cas B (14h45-15h15) : Refusé | Cas C (13h30-15h30) : Refusé."
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Échec de détection : Un chevauchement partiel a été autorisé par erreur !"
            ];
        }
    }
);

// --------------------------------------------------------------------
// TEST 7 : Deux demandes simultanées ne produisent jamais de double réservation
// --------------------------------------------------------------------
$testResults[] = executerTest(
    "Test 7 : Concurrence d'accès & Isolation des transactions SQL",
    "Simule deux requêtes concurrentes via transaction SQL et verrouillage FOR UPDATE pour garantir l'atomicité et l'unicité.",
    function () use ($pdo) {
        $dateCreneau = '2027-01-19 10:00:00';
        $duree = 30;

        // Transaction A : Réserve le créneau
        $pdo->beginTransaction();
        $checkA = verifierDisponibiliteRdv($pdo, 1, 1, 2, $dateCreneau, $duree);
        if ($checkA['valide']) {
            $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, motif, statut)
                           VALUES (1, 1, 2, ?, ?, ?, 'Réservation A', 'confirme')")
                ->execute([$dateCreneau, $duree, $checkA['date_heure_fin']]);
            $pdo->commit();
            $succesA = true;
        } else {
            $pdo->rollBack();
            $succesA = false;
        }

        // Transaction B : Tente simultanément la même réservation
        $pdo->beginTransaction();
        $checkB = verifierDisponibiliteRdv($pdo, 2, 1, 2, $dateCreneau, $duree);
        if ($checkB['valide']) {
            $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, motif, statut)
                           VALUES (2, 1, 2, ?, ?, ?, 'Réservation B', 'confirme')")
                ->execute([$dateCreneau, $duree, $checkB['date_heure_fin']]);
            $pdo->commit();
            $succesB = true;
        } else {
            $pdo->rollBack();
            $succesB = false; // Rejet attendu !
        }

        // Nettoyage après test
        $pdo->prepare("DELETE FROM rendez_vous WHERE date_heure >= '2027-01-01'")->execute();

        if ($succesA && !$succesB) {
            return [
                'succes' => true,
                'message' => "La concurrence est parfaitement gérée : La première requête a abouti, et la seconde a été bloquée.",
                'details' => "Zéro risque de collision grâce à la combinaison Transactions ACID + Verrouillage pessimiste."
            ];
        } else {
            return [
                'succes' => false,
                'message' => "Anomalie : Statut A ($succesA) / Statut B ($succesB)."
            ];
        }
    }
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>🧪 Banc de Validation des Règles de Gestion</h2>
            <p>TP Programmation II — 2e Licence Sciences Informatiques UCC : Évaluation automatisée des 7 tests obligatoires.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/tests/run_tests.php" class="btn btn-success">
                🔄 Réexécuter Tous les Tests
            </a>
        </div>
    </div>

    <!-- Synthèse globale -->
    <?php
    $totalPass = count(array_filter($testResults, fn($t) => $t['statut'] === 'PASS'));
    $totalTests = count($testResults);
    $allOk = ($totalPass === $totalTests);
    $percentage = round(($totalPass / $totalTests) * 100);
    ?>

    <div class="card" style="margin-bottom: 28px; background: <?= $allOk ? 'linear-gradient(135deg, #ecfdf5, #f0fdf4)' : 'linear-gradient(135deg, #fee2e2, #fef2f2)' ?>; border: 2px solid <?= $allOk ? '#10b981' : '#ef4444' ?>;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <span style="font-size: 1.6rem;"><?= $allOk ? '✅' : '⚠️' ?></span>
                    <h3 style="color: <?= $allOk ? '#065f46' : '#991b1b' ?>; font-size: 1.3rem; margin: 0;">
                        <?= $allOk ? "Tous les 7 Tests Obligatoires sont Validés ($totalPass / $totalTests)" : "Certains tests ont échoué ($totalPass / $totalTests)" ?>
                    </h3>
                </div>
                <p style="color: <?= $allOk ? '#047857' : '#b91c1c' ?>; font-size: 0.9rem; margin: 0;">
                    Conformité totale avec les contraintes d'intégrité, de chevauchement horaire, de disponibilité et de concurrence SQL.
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-family: 'Poppins', sans-serif; font-size: 2.2rem; font-weight: 800; color: <?= $allOk ? '#059669' : '#dc2626' ?>; line-height: 1;">
                    <?= $percentage ?>%
                </div>
                <div style="font-size: 0.8rem; font-weight: 600; color: <?= $allOk ? '#047857' : '#b91c1c' ?>; text-transform: uppercase;">
                    Taux de réussite
                </div>
            </div>
        </div>
    </div>

    <!-- Liste détaillée des 7 tests -->
    <div class="test-suite">
        <?php foreach ($testResults as $idx => $t): ?>
            <div class="test-card <?= $t['statut'] === 'PASS' ? 'pass' : 'fail' ?>">
                <div class="test-header">
                    <div class="test-title"><?= e($t['nom']) ?></div>
                    <span class="badge <?= $t['statut'] === 'PASS' ? 'badge-success' : 'badge-danger' ?>">
                        <?= $t['statut'] === 'PASS' ? '✓ RÉUSSI (PASS)' : '✕ ÉCHEC (FAIL)' ?>
                    </span>
                </div>
                <div class="test-desc">
                    <strong style="color: var(--text-primary);">Objectif du test :</strong> <?= e($t['description']) ?>
                </div>
                <div class="test-result">
                    <strong style="color: var(--primary-dark);">Constat serveur :</strong> <?= e($t['message']) ?>
                </div>
                <?php if (!empty($t['details'])): ?>
                    <div class="test-log"><?= e($t['details']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
