<?php
/**
 * ====================================================================
 * MODIFICATION / REPORT D'UN RENDEZ-VOUS
 * ====================================================================
 */

$pageTitle = "Reporter / Modifier un Rendez-vous";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Rendez-vous introuvable.");
    header('Location: ' . BASE_URL . '/rendez_vous/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE id = ?");
$stmt->execute([$id]);
$rdv = $stmt->fetch();
if (!$rdv) {
    flashMessage('danger', "Ce rendez-vous n'existe pas.");
    header('Location: ' . BASE_URL . '/rendez_vous/index.php');
    exit;
}

$errors = [];
$suggestions = [];

$id_patient = $rdv['id_patient'];
$id_medecin = $rdv['id_medecin'];
$id_salle = $rdv['id_salle'];
$date_heure = date('Y-m-d\TH:i', strtotime($rdv['date_heure']));
$duree_minutes = $rdv['duree_minutes'];
$equipement_requis = $rdv['equipement_requis'] ?? '';
$motif = $rdv['motif'];
$statut = $rdv['statut'];
$remarques = $rdv['remarques'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Erreur de sécurité CSRF. Veuillez rafraîchir la page.";
    }

    $id_patient = (int)($_POST['id_patient'] ?? 0);
    $id_medecin = (int)($_POST['id_medecin'] ?? 0);
    $id_salle = (int)($_POST['id_salle'] ?? 0);
    $raw_date_heure = trim($_POST['date_heure'] ?? '');
    $duree_minutes = (int)($_POST['duree_minutes'] ?? 30);
    $equipement_requis = trim($_POST['equipement_requis'] ?? '');
    $motif = trim($_POST['motif'] ?? '');
    $statut = trim($_POST['statut'] ?? 'confirme');
    $remarques = trim($_POST['remarques'] ?? '');

    $date_heure_mysql = date('Y-m-d H:i:s', strtotime($raw_date_heure));

    if (!$id_patient || !$id_medecin || !$id_salle || empty($raw_date_heure) || empty($motif)) {
        $errors[] = "Veuillez remplir tous les champs obligatoires.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmtLockMed = $pdo->prepare("SELECT id FROM medecins WHERE id = ? FOR UPDATE");
            $stmtLockMed->execute([$id_medecin]);

            $stmtLockSalle = $pdo->prepare("SELECT id FROM salles WHERE id = ? FOR UPDATE");
            $stmtLockSalle->execute([$id_salle]);

            // Vérification en excluant ce RDV spécifique (exclureIdRdv = $id)
            $check = verifierDisponibiliteRdv(
                $pdo,
                $id_patient,
                $id_medecin,
                $id_salle,
                $date_heure_mysql,
                $duree_minutes,
                $equipement_requis ?: null,
                $id
            );

            if (!$check['valide']) {
                $pdo->rollBack();
                $errors = $check['erreurs'];
                $suggestions = $check['suggestions'];
            } else {
                $date_heure_fin = $check['date_heure_fin'];

                $stmtUpdate = $pdo->prepare("UPDATE rendez_vous 
                    SET id_patient = ?, id_medecin = ?, id_salle = ?, date_heure = ?, duree_minutes = ?,
                        date_heure_fin = ?, equipement_requis = ?, motif = ?, statut = ?, remarques = ?
                    WHERE id = ?");

                $stmtUpdate->execute([
                    $id_patient, $id_medecin, $id_salle, $date_heure_mysql, $duree_minutes,
                    $date_heure_fin, $equipement_requis ?: null, $motif, $statut, $remarques ?: null, $id
                ]);

                $pdo->commit();
                flashMessage('success', "Le rendez-vous #$id a été mis à jour / reporté avec succès.");
                header('Location: ' . BASE_URL . '/rendez_vous/index.php');
                exit;
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Erreur SQL : " . $e->getMessage();
        }
    }
}

$patientsList = $pdo->query("SELECT id, nom, prenom FROM patients ORDER BY nom ASC")->fetchAll();
$medecinsList = $pdo->query("SELECT id, nom, prenom, specialite FROM medecins WHERE actif = 1 ORDER BY nom ASC")->fetchAll();
$sallesList   = $pdo->query("SELECT id, nom, statut FROM salles ORDER BY nom ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 900px;">

    <!-- En-tête de page -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>✏️ Modifier / Reporter le Rendez-vous #<?= $id ?></h2>
            <p>Ajustez les praticiens, la date ou le statut tout en maintenant l'absence de collision horaire.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary btn-sm">
                ← Retour au planning
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <div class="alert-icon">✕</div>
            <div class="alert-content">
                <strong>Conflit détecté lors du report :</strong>
                <ul style="margin: 6px 0 0 16px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($suggestions['autres_creneaux']) || !empty($suggestions['autres_salles'])): ?>
        <div class="suggestions-box">
            <div class="suggestions-title">
                <span>💡</span> Créneaux ou salles de repli suggérés
            </div>

            <?php if (!empty($suggestions['autres_creneaux'])): ?>
                <div class="suggestions-group">
                    <div class="suggestions-group-label">⏰ Horaires disponibles pour ce médecin le même jour :</div>
                    <div class="suggestions-btns">
                        <?php foreach ($suggestions['autres_creneaux'] as $cr): ?>
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                    onclick="document.getElementById('date_heure').value = document.getElementById('date_heure').value.split('T')[0] + 'T<?= $cr ?>'; actualiserApercuFin();">
                                <?= $cr ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($suggestions['autres_salles'])): ?>
                <div class="suggestions-group" style="margin-bottom: 0;">
                    <div class="suggestions-group-label">🏥 Salles libres sur ce créneau :</div>
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

    <div class="card" style="box-shadow: var(--s-md);">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Section 1 : Patient -->
            <div class="form-section" style="margin-top: 0;">
                <div class="form-section-title">
                    <span>👤</span> 1. Dossier Patient
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Patient concerné <span class="required">*</span></label>
                    <select name="id_patient" class="form-control" required>
                        <?php foreach ($patientsList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $id_patient === (int)$p['id'] ? 'selected' : '' ?>>
                                #<?= $p['id'] ?> — <?= e($p['nom']) ?> <?= e($p['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Section 2 : Praticien & Salle -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>🩺</span> 2. Médecin & Salle de Consultation
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Médecin traitant <span class="required">*</span></label>
                        <select name="id_medecin" class="form-control" required>
                            <?php foreach ($medecinsList as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $id_medecin === (int)$m['id'] ? 'selected' : '' ?>>
                                    <?= e($m['nom']) ?> <?= e($m['prenom']) ?> (<?= e($m['specialite']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Salle <span class="required">*</span></label>
                        <select name="id_salle" id="id_salle" class="form-control" required>
                            <?php foreach ($sallesList as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $id_salle === (int)$s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['nom']) ?> (<?= ucfirst($s['statut']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3 : Date & Durée -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>⏰</span> 3. Horaire & Durée
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Date & Heure Début <span class="required">*</span></label>
                        <input type="datetime-local" name="date_heure" id="date_heure" class="form-control" value="<?= e($date_heure) ?>" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Durée de consultation <span class="required">*</span></label>
                        <select name="duree_minutes" id="duree_minutes" class="form-control" required>
                            <option value="15" <?= $duree_minutes === 15 ? 'selected' : '' ?>>15 minutes</option>
                            <option value="30" <?= $duree_minutes === 30 ? 'selected' : '' ?>>30 minutes</option>
                            <option value="45" <?= $duree_minutes === 45 ? 'selected' : '' ?>>45 minutes</option>
                            <option value="60" <?= $duree_minutes === 60 ? 'selected' : '' ?>>60 minutes</option>
                            <option value="90" <?= $duree_minutes === 90 ? 'selected' : '' ?>>90 minutes</option>
                        </select>
                        <span id="apercu_heure_fin" class="badge badge-info" style="margin-top: 6px; display: none;"></span>
                    </div>
                </div>
            </div>

            <!-- Section 4 : Motif, Statut & Exigences -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>📋</span> 4. Motif & Statut Administratif
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Motif de consultation <span class="required">*</span></label>
                        <input type="text" name="motif" class="form-control" value="<?= e($motif) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Statut du RDV <span class="required">*</span></label>
                        <select name="statut" class="form-control" required>
                            <option value="confirme" <?= $statut === 'confirme' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="termine" <?= $statut === 'termine' ? 'selected' : '' ?>>Terminé</option>
                            <option value="annule" <?= $statut === 'annule' ? 'selected' : '' ?>>Annulé</option>
                            <option value="reporte" <?= $statut === 'reporte' ? 'selected' : '' ?>>Reporté</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Équipement Requis (Optionnel)</label>
                    <input type="text" name="equipement_requis" class="form-control" value="<?= e($equipement_requis) ?>" placeholder="Ex: ECG, Échographe...">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Remarques médicales</label>
                    <textarea name="remarques" class="form-control" style="min-height: 80px;"><?= e($remarques) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Valider la Modification / Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
