<?php
/**
 * ====================================================================
 * GESTION DES DISPONIBILITÉS ET INDISPONIBILITÉS (MÉDECINS & SALLES)
 * ====================================================================
 */

$pageTitle = "Gestion des Disponibilités & Congés";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$onglet = $_GET['tab'] ?? 'indisp_med';

// Traitement POST : Ajout d'une indisponibilité médecin (Absence, Congé, Réunion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_indisp_med'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', "Erreur de sécurité CSRF. Veuillez rafraîchir la page.");
    } else {
        $id_medecin = (int)$_POST['id_medecin'];
        $date_debut = trim($_POST['date_debut']);
        $date_fin = trim($_POST['date_fin']);
        $type = trim($_POST['type']);
        $motif = trim($_POST['motif']);

        if (!$id_medecin || empty($date_debut) || empty($date_fin)) {
            flashMessage('danger', "Veuillez remplir tous les champs obligatoires.");
        } elseif (strtotime($date_fin) <= strtotime($date_debut)) {
            flashMessage('danger', "La date/heure de fin doit être postérieure à la date de début.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO indisponibilites_medecins (id_medecin, date_debut, date_fin, type, motif) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_medecin, $date_debut, $date_fin, $type, $motif]);
            flashMessage('success', "Indisponibilité du médecin enregistrée. Aucun rendez-vous ne pourra être réservé sur ce créneau.");
            header('Location: ' . BASE_URL . '/disponibilites/index.php?tab=indisp_med');
            exit;
        }
    }
}

// Traitement POST : Ajout d'une indisponibilité salle (Maintenance, Désinfection)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_indisp_salle'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', "Erreur de sécurité CSRF.");
    } else {
        $id_salle = (int)$_POST['id_salle'];
        $date_debut = trim($_POST['date_debut']);
        $date_fin = trim($_POST['date_fin']);
        $type = trim($_POST['type']);
        $motif = trim($_POST['motif']);

        if (!$id_salle || empty($date_debut) || empty($date_fin)) {
            flashMessage('danger', "Veuillez remplir tous les champs obligatoires pour la salle.");
        } elseif (strtotime($date_fin) <= strtotime($date_debut)) {
            flashMessage('danger', "La date de fin doit être supérieure à la date de début.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO indisponibilites_salles (id_salle, date_debut, date_fin, type, motif) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_salle, $date_debut, $date_fin, $type, $motif]);
            flashMessage('success', "Période de maintenance de la salle enregistrée avec succès.");
            header('Location: ' . BASE_URL . '/disponibilites/index.php?tab=indisp_salle');
            exit;
        }
    }
}

// Traitement POST : Ajout d'un horaire régulier médecin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_horaire_med'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', "Erreur de sécurité CSRF.");
    } else {
        $id_medecin = (int)$_POST['id_medecin'];
        $jour = trim($_POST['jour_semaine']);
        $h_deb = trim($_POST['heure_debut']);
        $h_fin = trim($_POST['heure_fin']);

        if (!$id_medecin || empty($jour) || empty($h_deb) || empty($h_fin)) {
            flashMessage('danger', "Tous les champs sont requis.");
        } elseif ($h_fin <= $h_deb) {
            flashMessage('danger', "L'heure de fin doit être supérieure à l'heure de début.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO disponibilites_medecins (id_medecin, jour_semaine, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_medecin, $jour, $h_deb . ':00', $h_fin . ':00']);
            flashMessage('success', "Plage horaire ajoutée au planning du médecin.");
            header('Location: ' . BASE_URL . '/disponibilites/index.php?tab=horaires_med');
            exit;
        }
    }
}

// Suppression rapide d'une indisponibilité
if (isset($_GET['suppr_indisp_med'])) {
    $idSuppr = (int)$_GET['suppr_indisp_med'];
    $pdo->prepare("DELETE FROM indisponibilites_medecins WHERE id = ?")->execute([$idSuppr]);
    flashMessage('success', "Indisponibilité retirée avec succès.");
    header('Location: ' . BASE_URL . '/disponibilites/index.php?tab=indisp_med');
    exit;
}

if (isset($_GET['suppr_indisp_salle'])) {
    $idSuppr = (int)$_GET['suppr_indisp_salle'];
    $pdo->prepare("DELETE FROM indisponibilites_salles WHERE id = ?")->execute([$idSuppr]);
    flashMessage('success', "Indisponibilité de la salle retirée avec succès.");
    header('Location: ' . BASE_URL . '/disponibilites/index.php?tab=indisp_salle');
    exit;
}

// Récupération des données d'affichage
$medecinsList = $pdo->query("SELECT id, nom, prenom, specialite FROM medecins WHERE actif = 1 ORDER BY nom ASC")->fetchAll();
$sallesList   = $pdo->query("SELECT id, nom FROM salles ORDER BY nom ASC")->fetchAll();

$indispMedecins = $pdo->query("SELECT im.*, m.nom, m.prenom, m.specialite 
                               FROM indisponibilites_medecins im 
                               JOIN medecins m ON im.id_medecin = m.id 
                               ORDER BY im.date_debut DESC")->fetchAll();

$indispSalles = $pdo->query("SELECT isal.*, s.nom AS salle_nom 
                             FROM indisponibilites_salles isal 
                             JOIN salles s ON isal.id_salle = s.id 
                             ORDER BY isal.date_debut DESC")->fetchAll();

$horairesMedecins = $pdo->query("SELECT dm.*, m.nom, m.prenom 
                                 FROM disponibilites_medecins dm 
                                 JOIN medecins m ON dm.id_medecin = m.id 
                                 ORDER BY m.nom ASC, FIELD(dm.jour_semaine, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche')")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page moderne Centre Médical Hope -->
    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div>
                <div class="page-banner-title">Disponibilités</div>
                <div class="page-banner-sub">Consultez les créneaux disponibles des médecins et les plages de maintenance des salles</div>
            </div>
        </div>
    </div>

    <!-- Onglets de navigation stylisés -->
    <div class="tab-nav">
        <a href="?tab=indisp_med" class="tab-btn <?= $onglet === 'indisp_med' ? 'active' : '' ?>">
            <span>👨‍⚕️</span> Congés & Absences Médecins (<?= count($indispMedecins) ?>)
        </a>
        <a href="?tab=indisp_salle" class="tab-btn <?= $onglet === 'indisp_salle' ? 'active' : '' ?>">
            <span>🏥</span> Maintenance des Salles (<?= count($indispSalles) ?>)
        </a>
        <a href="?tab=horaires_med" class="tab-btn <?= $onglet === 'horaires_med' ? 'active' : '' ?>">
            <span>⏰</span> Horaires Réguliers de Travail
        </a>
    </div>

    <?php if ($onglet === 'indisp_med'): ?>
        <!-- ============================================================ -->
        <!-- ONGLET 1 : INDISPONIBILITÉS MÉDECINS -->
        <!-- ============================================================ -->
        <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 28px; align-items: start;">
            <!-- Formulaire ajout -->
            <div class="card card-3d">
                <div class="card-header" style="margin-bottom: 16px; padding-bottom: 12px;">
                    <div class="card-title">
                        <span>➕</span> Déclarer une Indisponibilité
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action_indisp_med" value="1">

                    <div class="form-group">
                        <label class="form-label">Médecin concerné <span class="required">*</span></label>
                        <select name="id_medecin" class="form-control" required>
                            <option value="">-- Choisir un médecin --</option>
                            <?php foreach ($medecinsList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['nom']) ?> <?= e($m['prenom']) ?> (<?= e($m['specialite']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Motif / Type <span class="required">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="absence">Absence ponctuelle</option>
                            <option value="conge">Congé annuel / maladie</option>
                            <option value="reunion">Réunion administrative / staff</option>
                            <option value="formation">Formation médicale continue</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date & Heure de début <span class="required">*</span></label>
                        <input type="datetime-local" name="date_debut" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date & Heure de fin <span class="required">*</span></label>
                        <input type="datetime-local" name="date_fin" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Précisions ou motif</label>
                        <input type="text" name="motif" class="form-control" placeholder="Ex: Réunion comité UCC, Congrès...">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        🛡️ Enregistrer l'Indisponibilité
                    </button>
                </form>
            </div>

            <!-- Liste des indisponibilités -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>📋</span> Absences & Réunions Planifiées
                    </div>
                    <span class="badge badge-warning"><?= count($indispMedecins) ?> enregistrée(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Médecin</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Motif</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($indispMedecins)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">
                                        <div class="table-empty-icon">✓</div>
                                        <div style="font-weight: 600; color: var(--text-primary);">Aucune absence déclarée</div>
                                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Tous les médecins sont disponibles selon leurs horaires habituels.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($indispMedecins as $im): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text-primary);"><?= e($im['nom']) ?> <?= e($im['prenom']) ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($im['specialite']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning"><?= ucfirst(e($im['type'])) ?></span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.825rem; font-weight: 600; color: var(--text-primary);">
                                                Du <?= date('d/m/Y H:i', strtotime($im['date_debut'])) ?>
                                            </div>
                                            <div style="font-size: 0.775rem; color: var(--text-muted);">
                                                Au <?= date('d/m/Y H:i', strtotime($im['date_fin'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.825rem;"><?= e($im['motif'] ?: '—') ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="?tab=indisp_med&suppr_indisp_med=<?= $im['id'] ?>" 
                                               class="btn btn-xs btn-outline-danger btn-confirm-delete"
                                               data-confirm="Supprimer cette indisponibilité ? Le médecin redeviendra réservable sur ce créneau.">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($onglet === 'indisp_salle'): ?>
        <!-- ============================================================ -->
        <!-- ONGLET 2 : INDISPONIBILITÉS SALLES (MAINTENANCE) -->
        <!-- ============================================================ -->
        <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 28px; align-items: start;">
            <div class="card card-3d">
                <div class="card-header" style="margin-bottom: 16px; padding-bottom: 12px;">
                    <div class="card-title">
                        <span>➕</span> Planifier une Maintenance
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action_indisp_salle" value="1">

                    <div class="form-group">
                        <label class="form-label">Salle concernée <span class="required">*</span></label>
                        <select name="id_salle" class="form-control" required>
                            <option value="">-- Choisir une salle --</option>
                            <?php foreach ($sallesList as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type d'intervention <span class="required">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="maintenance">Maintenance technique</option>
                            <option value="desinfection">Désinfection hospitalière</option>
                            <option value="reparation">Réparation d'équipement</option>
                            <option value="fermeture">Fermeture exceptionnelle</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date & Heure Début <span class="required">*</span></label>
                        <input type="datetime-local" name="date_debut" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date & Heure Fin <span class="required">*</span></label>
                        <input type="datetime-local" name="date_fin" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Motif / Nature des travaux</label>
                        <input type="text" name="motif" class="form-control" placeholder="Ex: Révision du moniteur, peinture, étalonnage...">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        🛡️ Bloquer la Salle
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>🏥</span> Maintenances & Fermetures Planifiées
                    </div>
                    <span class="badge badge-danger"><?= count($indispSalles) ?> intervention(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Salle</th>
                                <th>Intervention</th>
                                <th>Période</th>
                                <th>Motif</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($indispSalles)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">
                                        <div class="table-empty-icon">✓</div>
                                        <div style="font-weight: 600; color: var(--text-primary);">Aucune maintenance en cours</div>
                                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Toutes les salles sont actuellement opérationnelles.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($indispSalles as $is): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text-primary);"><?= e($is['salle_nom']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-danger"><?= ucfirst(e($is['type'])) ?></span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.825rem; font-weight: 600; color: var(--text-primary);">
                                                Du <?= date('d/m/Y H:i', strtotime($is['date_debut'])) ?>
                                            </div>
                                            <div style="font-size: 0.775rem; color: var(--text-muted);">
                                                Au <?= date('d/m/Y H:i', strtotime($is['date_fin'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.825rem;"><?= e($is['motif'] ?: '—') ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="?tab=indisp_salle&suppr_indisp_salle=<?= $is['id'] ?>" 
                                               class="btn btn-xs btn-outline-danger btn-confirm-delete"
                                               data-confirm="Débloquer cette salle ?">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($onglet === 'horaires_med'): ?>
        <!-- ============================================================ -->
        <!-- ONGLET 3 : HORAIRES RÉGULIERS -->
        <!-- ============================================================ -->
        <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 28px; align-items: start;">
            <div class="card card-3d">
                <div class="card-header" style="margin-bottom: 16px; padding-bottom: 12px;">
                    <div class="card-title">
                        <span>➕</span> Ajouter une Plage de Travail
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action_horaire_med" value="1">

                    <div class="form-group">
                        <label class="form-label">Médecin <span class="required">*</span></label>
                        <select name="id_medecin" class="form-control" required>
                            <option value="">-- Choisir un médecin --</option>
                            <?php foreach ($medecinsList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['nom']) ?> <?= e($m['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jour de la semaine <span class="required">*</span></label>
                        <select name="jour_semaine" class="form-control" required>
                            <option value="Lundi">Lundi</option>
                            <option value="Mardi">Mardi</option>
                            <option value="Mercredi">Mercredi</option>
                            <option value="Jeudi">Jeudi</option>
                            <option value="Vendredi">Vendredi</option>
                            <option value="Samedi">Samedi</option>
                            <option value="Dimanche">Dimanche</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Heure Début <span class="required">*</span></label>
                            <input type="time" name="heure_debut" class="form-control" value="08:00" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Heure Fin <span class="required">*</span></label>
                            <input type="time" name="heure_fin" class="form-control" value="16:00" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        💾 Ajouter cette Plage
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>⏰</span> Grille des Plages de Travail Hebdomadaires
                    </div>
                    <span class="badge badge-primary"><?= count($horairesMedecins) ?> créneaux configurés</span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Médecin</th>
                                <th>Jour</th>
                                <th>Horaires de consultation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($horairesMedecins)): ?>
                                <tr>
                                    <td colspan="3" class="table-empty">
                                        <div class="table-empty-icon">⏰</div>
                                        <div style="font-weight: 600; color: var(--text-primary);">Aucun horaire configuré</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($horairesMedecins as $hm): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text-primary);"><?= e($hm['nom']) ?> <?= e($hm['prenom']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary"><?= e($hm['jour_semaine']) ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: var(--primary);">
                                                <?= substr($hm['heure_debut'], 0, 5) ?> – <?= substr($hm['heure_fin'], 0, 5) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
