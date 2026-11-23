<?php
/**
 * ====================================================================
 * PRISE DE RENDEZ-VOUS AVEC APPLICATION STRICTE DES RÈGLES DE GESTION
 * ====================================================================
 * TP Programmation II - UCC
 * Implémente :
 * - Authentification requise (Patient connecté OU Personnel staff)
 * - Transactions SQL et verrouillage pessimiste (FOR UPDATE)
 * - Détection intégrale des chevauchements horaires partiels et complets
 * - Vérification de la présence du médecin, de ses congés et réunions
 * - Vérification de l'état de la salle et de ses équipements requis
 * - Proposition intelligente d'alternatives en cas de conflit
 */

$pageTitle = "Prendre un Rendez-vous";
require_once __DIR__ . '/../config/database.php';

// RÈGLE D'ACCÈS : Le patient ou le staff DOIT être connecté pour réserver
if (!isLoggedIn() && !isPatientLoggedIn()) {
    flashMessage('warning', 'Pour prendre un rendez-vous, veuillez vous connecter ou créer votre compte patient.');
    $_SESSION['redirect_after_login'] = BASE_URL . '/rendez_vous/prendre.php';
    header('Location: ' . BASE_URL . '/patients/connexion.php');
    exit;
}

$isPatientUser = isPatientLoggedIn() && !isLoggedIn();
$currentPatientData = null;

if ($isPatientUser) {
    // Si c'est un patient connecté, on force son propre ID
    $id_patient = (int)$_SESSION['patient_id'];
    $stmtCurP = $pdo->prepare("SELECT id, nom, prenom, telephone, email FROM patients WHERE id = ?");
    $stmtCurP->execute([$id_patient]);
    $currentPatientData = $stmtCurP->fetch();
} else {
    // Si c'est un membre du staff (admin ou réceptionniste)
    $prePatientId = (int)($_GET['patient_id'] ?? 0);
    $id_patient = $prePatientId;
}

$errors = [];
$suggestions = [];

// Pré-remplissage via URL si disponible
$preMedecinId = (int)($_GET['medecin_id'] ?? 0);
$preSalleId = (int)($_GET['salle_id'] ?? 0);

// Valeurs par défaut du formulaire
$id_medecin = $preMedecinId;
$id_salle = $preSalleId;
$date_heure = date('Y-m-d\TH:i', strtotime('+1 day 09:00'));
$duree_minutes = 30;
$equipement_requis = '';
$motif = '';
$remarques = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Erreur de sécurité : Jeton CSRF invalide. Veuillez rafraîchir la page.";
    }

    // Si patient connecté, on force son ID pour empêcher toute falsification de formulaire
    if ($isPatientUser) {
        $id_patient = (int)$_SESSION['patient_id'];
    } else {
        $id_patient = (int)($_POST['id_patient'] ?? 0);
    }

    $id_medecin = (int)($_POST['id_medecin'] ?? 0);
    $id_salle = (int)($_POST['id_salle'] ?? 0);
    $raw_date_heure = trim($_POST['date_heure'] ?? '');
    $duree_minutes = (int)($_POST['duree_minutes'] ?? 30);
    $equipement_requis = trim($_POST['equipement_requis'] ?? '');
    $motif = trim($_POST['motif'] ?? '');
    $remarques = trim($_POST['remarques'] ?? '');

    $date_heure_mysql = date('Y-m-d H:i:s', strtotime($raw_date_heure));

    // Validation des champs requis
    if (!$id_patient) $errors[] = "Veuillez sélectionner un patient.";
    if (!$id_medecin) $errors[] = "Veuillez choisir un médecin traitant.";
    if (!$id_salle) $errors[] = "Veuillez assigner une salle de consultation.";
    if (empty($raw_date_heure)) $errors[] = "La date et l'heure du rendez-vous sont requises.";
    if (empty($motif)) $errors[] = "Veuillez renseigner le motif de la consultation.";
    if (!in_array($duree_minutes, [15, 30, 45, 60, 90])) $duree_minutes = 30;

    if (empty($errors)) {
        try {
            // TRANSACTION SQL & VERROUILLAGE PESSIMISTE
            $pdo->beginTransaction();

            $stmtLockMed = $pdo->prepare("SELECT id FROM medecins WHERE id = ? FOR UPDATE");
            $stmtLockMed->execute([$id_medecin]);

            $stmtLockSalle = $pdo->prepare("SELECT id FROM salles WHERE id = ? FOR UPDATE");
            $stmtLockSalle->execute([$id_salle]);

            // Appeler le moteur central de vérification des règles
            $resultatVerification = verifierDisponibiliteRdv(
                $pdo,
                $id_patient,
                $id_medecin,
                $id_salle,
                $date_heure_mysql,
                $duree_minutes,
                $equipement_requis ?: null
            );

            if (!$resultatVerification['valide']) {
                $pdo->rollBack();
                $errors = $resultatVerification['erreurs'];
                $suggestions = $resultatVerification['suggestions'];
            } else {
                $date_heure_fin = $resultatVerification['date_heure_fin'];

                $stmtInsert = $pdo->prepare("INSERT INTO rendez_vous 
                    (id_patient, id_medecin, id_salle, date_heure, duree_minutes, date_heure_fin, equipement_requis, motif, statut, remarques)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirme', ?)");
                
                $stmtInsert->execute([
                    $id_patient,
                    $id_medecin,
                    $id_salle,
                    $date_heure_mysql,
                    $duree_minutes,
                    $date_heure_fin,
                    $equipement_requis ?: null,
                    $motif,
                    $remarques ?: null
                ]);

                $rdvId = $pdo->lastInsertId();
                $pdo->commit();

                // ----------------------------------------------------
                // ENVOI DE L'EMAIL DE CONFIRMATION AU PATIENT
                // ----------------------------------------------------
                require_once __DIR__ . '/../includes/mailer.php';
                $emailResult = envoyerEmailConfirmationRdv($rdvId, $pdo);

                flashMessage('success', "Le rendez-vous #$rdvId a été confirmé avec succès pour le " . date('d/m/Y à H:i', strtotime($date_heure_mysql)) . ". Un email de confirmation a été transmis.");
                
                // Redirection vers la fiche imprimable
                header('Location: ' . BASE_URL . '/rendez_vous/fiche.php?id=' . $rdvId . '&success=1');
                exit;
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Erreur système lors de la réservation : " . $e->getMessage();
        }
    }
}

// Données pour les listes déroulantes
if (!$isPatientUser) {
    $patientsList = $pdo->query("SELECT id, nom, prenom, telephone FROM patients ORDER BY nom ASC")->fetchAll();
}
$medecinsList = $pdo->query("SELECT id, nom, prenom, specialite FROM medecins WHERE actif = 1 ORDER BY nom ASC")->fetchAll();
$sallesList = $pdo->query("SELECT id, nom, equipements, statut FROM salles ORDER BY nom ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1200px;">

    <!-- En-tête de page moderne Centre Médical Hope -->
    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div>
                <div class="page-banner-title">Prise de rendez-vous</div>
                <div class="page-banner-sub">Planifier une consultation avec vérification instantanée des disponibilités</div>
            </div>
        </div>
        <div>
            <?php if ($isPatientUser): ?>
                <a href="<?= BASE_URL ?>/patients/espace.php" class="btn btn-secondary">
                    ← Retour à mon espace
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary">
                    ← Liste des rendez-vous
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertes d'erreurs et conflits détectés -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <div class="alert-icon">✕</div>
            <div class="alert-content">
                <strong>Réservation impossible (conflit détecté) :</strong>
                <ul style="margin: 8px 0 0 18px; line-height: 1.5;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Boîte de suggestions en cas de conflit -->
    <?php if (!empty($suggestions['autres_salles']) || !empty($suggestions['autres_creneaux'])): ?>
        <div class="suggestions-box" style="margin-bottom: 24px;">
            <div class="suggestions-title">
                <span>💡</span> Alternatives disponibles proposées par le système
            </div>

            <?php if (!empty($suggestions['autres_creneaux'])): ?>
                <div class="suggestions-group">
                    <div class="suggestions-group-label">⏰ Autres créneaux libres pour ce médecin le même jour :</div>
                    <div class="suggestions-btns">
                        <?php foreach ($suggestions['autres_creneaux'] as $cr): ?>
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                    onclick="document.getElementById('date_heure').value = document.getElementById('date_heure').value.split('T')[0] + 'T<?= $cr ?>'; actualiserApercuFin();">
                                Choisir <?= $cr ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($suggestions['autres_salles'])): ?>
                <div class="suggestions-group" style="margin-bottom: 0;">
                    <div class="suggestions-group-label">🏥 Salles libres et disponibles sur ce même créneau horaire :</div>
                    <div class="suggestions-btns">
                        <?php foreach ($suggestions['autres_salles'] as $as): ?>
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                    onclick="document.getElementById('id_salle').value = '<?= $as['id'] ?>';">
                                Assigner : <?= e($as['nom']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Grille 2 colonnes (Formulaire à gauche + Carte d'accompagnement à droite) -->
    <div class="responsive-2col-grid">
        
        <!-- Formulaire de réservation -->
        <div class="card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--r-xl); padding: 26px; box-shadow: var(--shadow-sm);">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <!-- Section 1 : Patient -->
                <div class="form-section" style="margin-top: 0;">
                    <div class="form-section-title">
                        <span>👤</span> 1. Patient Bénéficiaire
                    </div>

                    <?php if ($isPatientUser): ?>
                        <!-- Patient connecté : info figée sécurisée -->
                        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-weight: 700; color: #065f46; font-size: 0.98rem;">
                                    <?= e($currentPatientData['prenom']) ?> <?= e($currentPatientData['nom']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #047857; margin-top: 2px;">
                                    Dossier patient #<?= $currentPatientData['id'] ?> • Tél : <?= e($currentPatientData['telephone']) ?> • <?= e($currentPatientData['email']) ?>
                                </div>
                            </div>
                            <span class="badge" style="background: #059669; color: #fff; font-size: 0.72rem; padding: 4px 8px; border-radius: 6px;">
                                Vous (Patient connecté)
                            </span>
                        </div>
                    <?php else: ?>
                        <!-- Staff connecté : choix parmi tous les patients -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px;">
                                <label class="form-label" style="margin-bottom: 0;">Dossier Patient <span class="required">*</span></label>
                                <a href="<?= BASE_URL ?>/patients/ajouter.php?redirect_rdv=1" style="font-size: 0.8rem; font-weight: 600; color: var(--teal-500);">
                                    + Inscrire un nouveau patient
                                </a>
                            </div>
                            <select name="id_patient" class="form-control" required>
                                <option value="">-- Sélectionner un patient dans la base --</option>
                                <?php foreach ($patientsList as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $id_patient === (int)$p['id'] ? 'selected' : '' ?>>
                                        #<?= $p['id'] ?> — <?= e($p['nom']) ?> <?= e($p['prenom']) ?> (<?= e($p['telephone']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section 2 : Praticien et Localisation -->
                <div class="form-section">
                    <div class="form-section-title">
                        <span>🩺</span> 2. Praticien et Salle
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Médecin traitant <span class="required">*</span></label>
                            <select name="id_medecin" id="id_medecin" class="form-control" required>
                                <option value="">-- Sélectionner un médecin --</option>
                                <?php foreach ($medecinsList as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $id_medecin === (int)$m['id'] ? 'selected' : '' ?>>
                                        <?= e($m['nom']) ?> <?= e($m['prenom']) ?> (<?= e($m['specialite']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Salle de consultation <span class="required">*</span></label>
                            <select name="id_salle" id="id_salle" class="form-control" required>
                                <option value="">-- Sélectionner une salle --</option>
                                <?php foreach ($sallesList as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $id_salle === (int)$s['id'] ? 'selected' : '' ?>>
                                        <?= e($s['nom']) ?> [<?= ucfirst($s['statut']) ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 3 : Date, Horaires & Durée -->
                <div class="form-section">
                    <div class="form-section-title">
                        <span>⏰</span> 3. Date, Heure et Durée
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Date & Heure de début <span class="required">*</span></label>
                            <input type="datetime-local" name="date_heure" id="date_heure" class="form-control" value="<?= e($date_heure) ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Durée de la séance <span class="required">*</span></label>
                            <select name="duree_minutes" id="duree_minutes" class="form-control" required>
                                <option value="15" <?= $duree_minutes === 15 ? 'selected' : '' ?>>15 minutes (Visite rapide)</option>
                                <option value="30" <?= $duree_minutes === 30 ? 'selected' : '' ?>>30 minutes (Standard)</option>
                                <option value="45" <?= $duree_minutes === 45 ? 'selected' : '' ?>>45 minutes (Examen approfondi)</option>
                                <option value="60" <?= $duree_minutes === 60 ? 'selected' : '' ?>>60 minutes (Bilan complet)</option>
                                <option value="90" <?= $duree_minutes === 90 ? 'selected' : '' ?>>90 minutes (Soin spécialisé)</option>
                            </select>
                            <span id="apercu_heure_fin" class="badge badge-info" style="margin-top: 8px; display: none;"></span>
                        </div>
                    </div>
                </div>

                <!-- Section 4 : Motif & Exigences médicales -->
                <div class="form-section">
                    <div class="form-section-title">
                        <span>📋</span> 4. Motif & Exigences Techniques
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Motif de consultation <span class="required">*</span></label>
                            <input type="text" name="motif" class="form-control" placeholder="Ex: Consultation cardiologie, bilan de contrôle..." value="<?= e($motif) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Équipement spécifique (Optionnel)</label>
                            <input type="text" name="equipement_requis" class="form-control" placeholder="Ex: ECG, Échographe..." value="<?= e($equipement_requis) ?>">
                            <span class="form-hint">Le moteur vérifiera que la salle dispose bien de cet équipement.</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Remarques ou consignes particulières</label>
                        <textarea name="remarques" class="form-control" style="min-height: 75px;" placeholder="Ex: Antécédents allergiques, patient sous traitement..."><?= e($remarques) ?></textarea>
                    </div>
                </div>

                <!-- Boutons de soumission -->
                <div class="form-actions" style="margin-top: 24px;">
                    <?php if ($isPatientUser): ?>
                        <a href="<?= BASE_URL ?>/patients/espace.php" class="btn btn-secondary">
                            Annuler
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary">
                            Annuler
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #059669, #047857); border: none;">
                        Confirmer le rendez-vous
                    </button>
                </div>
            </form>
        </div>

        <!-- Panneau latéral d'accompagnement (style mockup Centre Médical Hope) -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="card" style="background: linear-gradient(135deg, #e3f5eb 0%, #edf9f2 100%); border: 1px solid #ccebdb; border-radius: var(--r-xl); padding: 24px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background-color: var(--teal-500); color: #fff; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #082c1e; margin: 0;">Une consultation au bon moment</h3>
                </div>
                <p style="font-size: 0.85rem; color: #0d4430; line-height: 1.5; margin-bottom: 16px;">
                    Des soins de qualité, en toute confiance. Notre moteur de réservation garantit zéro chevauchement horaire.
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.825rem; color: #06432b;">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: #0d9488; font-weight: 700;">✓</span>
                        <span>Vérification automatique de la disponibilité du praticien</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: #0d9488; font-weight: 700;">✓</span>
                        <span>Contrôle d'attribution exclusive de la salle d'examen</span>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: #0d9488; font-weight: 700;">✓</span>
                        <span>Validation des équipements médicaux nécessaires</span>
                    </div>
                </div>
            </div>

            <div class="card" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--r-xl); padding: 22px;">
                <h4 style="font-size: 0.925rem; font-weight: 700; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <span>📞</span> Assistance & Urgences
                </h4>
                <p style="font-size: 0.825rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    Pour les cas urgents ou des modifications de dernière minute, contactez directement l'accueil clinique :
                </p>
                <div style="background-color: #f8fafc; border-radius: 8px; padding: 10px 14px; font-size: 0.85rem; font-weight: 600; color: var(--teal-500);">
                    +243 81 000 0000 &bull; 24h/24
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
