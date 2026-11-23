<?php
/**
 * ====================================================================
 * FICHE PATIENT & HISTORIQUE DES RENDEZ-VOUS
 * ====================================================================
 */

$pageTitle = "Dossier Médical du Patient";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Identifiant de patient invalide.");
    header('Location: ' . BASE_URL . '/patients/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) {
    flashMessage('danger', "Dossier patient introuvable.");
    header('Location: ' . BASE_URL . '/patients/index.php');
    exit;
}

// Calcul de l'âge
$birthDate = new DateTime($patient['date_naissance']);
$age = $birthDate->diff(new DateTime())->y;

// Historique des rendez-vous
$stmtRdv = $pdo->prepare("SELECT r.*, m.nom AS medecin_nom, m.prenom AS medecin_prenom, m.specialite, s.nom AS salle_nom
                         FROM rendez_vous r
                         JOIN medecins m ON r.id_medecin = m.id
                         JOIN salles s ON r.id_salle = s.id
                         WHERE r.id_patient = ?
                         ORDER BY r.date_heure DESC");
$stmtRdv->execute([$id]);
$historique = $stmtRdv->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>📋 Dossier Patient #<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?> : <?= e($patient['nom']) ?> <?= e($patient['prenom']) ?></h2>
            <p>Inscrit dans le système clinique depuis le <?= date('d/m/Y', strtotime($patient['date_creation'])) ?></p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php?patient_id=<?= $id ?>" class="btn btn-primary">
                📅 Programmer un RDV
            </a>
            <a href="<?= BASE_URL ?>/patients/modifier.php?id=<?= $id ?>" class="btn btn-secondary">
                ✏️ Modifier la Fiche
            </a>
            <a href="<?= BASE_URL ?>/patients/index.php" class="btn btn-ghost">
                ← Retour au répertoire
            </a>
        </div>
    </div>

    <!-- Grille Fiche Patient (2 colonnes) -->
    <div class="two-col" style="margin-bottom: 32px;">

        <!-- Carte Identité & Coordonnées -->
        <div class="card card-3d">
            <div class="card-header" style="margin-bottom: 12px; padding-bottom: 12px;">
                <div class="card-title">
                    <span>👤</span> Identité & Coordonnées
                </div>
                <span class="badge <?= $patient['sexe'] === 'M' ? 'badge-info' : 'badge-primary' ?>">
                    <?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>
                </span>
            </div>

            <div class="dossier-info-row">
                <span class="dossier-info-label">Nom complet</span>
                <span class="dossier-info-value" style="font-size: 1.05rem; font-weight: 700;">
                    <?= e($patient['nom']) ?> <?= e($patient['prenom']) ?>
                </span>
            </div>

            <div class="dossier-info-row">
                <span class="dossier-info-label">Âge & Date de naissance</span>
                <span class="dossier-info-value">
                    <strong><?= $age ?> ans</strong> (né le <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>)
                </span>
            </div>

            <div class="dossier-info-row">
                <span class="dossier-info-label">Numéro de Téléphone</span>
                <span class="dossier-info-value">📞 <?= e($patient['telephone']) ?></span>
            </div>

            <div class="dossier-info-row">
                <span class="dossier-info-label">Adresse Email</span>
                <span class="dossier-info-value"><?= e($patient['email'] ?: 'Non renseignée') ?></span>
            </div>

            <div class="dossier-info-row">
                <span class="dossier-info-label">Adresse de résidence</span>
                <span class="dossier-info-value">📍 <?= e($patient['adresse'] ?: 'Non renseignée') ?></span>
            </div>

            <div class="dossier-info-row" style="border-bottom: none;">
                <span class="dossier-info-label">Groupe Sanguin</span>
                <span class="dossier-info-value">
                    <?php if ($patient['groupe_sanguin']): ?>
                        <span class="badge badge-danger" style="font-size: 0.85rem;"><?= e($patient['groupe_sanguin']) ?></span>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">Non renseigné</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- Carte Antécédents & Remarques Médicales -->
        <div class="card card-3d">
            <div class="card-header" style="margin-bottom: 12px; padding-bottom: 12px;">
                <div class="card-title">
                    <span>🩺</span> Antécédents Médicaux & Allergies
                </div>
                <span class="badge badge-teal">Dossier Clinique</span>
            </div>

            <?php if (!empty($patient['antecedents'])): ?>
                <div style="background: var(--warning-light); border-left: 4px solid var(--warning); padding: 16px; border-radius: var(--r-sm); color: #92400e; font-size: 0.9rem; line-height: 1.6;">
                    <strong>Remarques consignées :</strong><br>
                    <?= nl2br(e($patient['antecedents'])) ?>
                </div>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.9rem; font-style: italic;">
                    Aucun antécédent médical particulier consigné dans ce dossier.
                </div>
            <?php endif; ?>

            <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border);">
                <div class="section-label">Activité & Fréquentation</div>
                <div style="display: flex; gap: 24px; margin-top: 8px;">
                    <div>
                        <div style="font-size: 1.6rem; font-weight: 700; color: var(--primary);">
                            <?= count($historique) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-secondary);">Consultations totales</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Historique des consultations du patient -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>🗓️</span> Historique des Rendez-vous & Consultations
            </div>
            <span class="badge badge-secondary"><?= count($historique) ?> consultation<?= count($historique) > 1 ? 's' : '' ?></span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Durée</th>
                        <th>Médecin traitant</th>
                        <th>Salle</th>
                        <th>Motif de consultation</th>
                        <th>Statut</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historique)): ?>
                        <tr>
                            <td colspan="7" class="table-empty">
                                <div class="table-empty-icon">📅</div>
                                <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                    Aucune consultation enregistrée
                                </div>
                                <p style="font-size: 0.825rem; color: var(--text-secondary); margin-bottom: 12px;">
                                    Ce patient n'a pas encore de rendez-vous programmé.
                                </p>
                                <a href="<?= BASE_URL ?>/rendez_vous/prendre.php?patient_id=<?= $id ?>" class="btn btn-primary btn-sm">
                                    + Prendre un premier rendez-vous
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historique as $rdv): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($rdv['date_heure'])) ?></strong>
                                    <div style="color: var(--primary); font-size: 0.8rem; font-weight: 600; margin-top: 2px;">
                                        <?= date('H:i', strtotime($rdv['date_heure'])) ?> – <?= date('H:i', strtotime($rdv['date_heure_fin'])) ?>
                                    </div>
                                </td>
                                <td><?= $rdv['duree_minutes'] ?> min</td>
                                <td>
                                    <strong><?= e($rdv['medecin_nom']) ?> <?= e($rdv['medecin_prenom']) ?></strong><br>
                                    <span class="badge badge-primary" style="font-size: 0.68rem;"><?= e($rdv['specialite']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= e($rdv['salle_nom']) ?></span>
                                </td>
                                <td>
                                    <strong><?= e($rdv['motif']) ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $st = $rdv['statut'];
                                    $cls = 'badge-secondary';
                                    if ($st === 'confirme') $cls = 'badge-success';
                                    elseif ($st === 'en_attente') $cls = 'badge-warning';
                                    elseif ($st === 'annule') $cls = 'badge-danger';
                                    elseif ($st === 'termine') $cls = 'badge-info';
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= ucfirst($st) ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= BASE_URL ?>/rendez_vous/modifier.php?id=<?= $rdv['id'] ?>" class="btn btn-xs btn-outline-primary">
                                        Reporter
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
