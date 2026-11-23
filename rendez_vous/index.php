<?php
/**
 * ====================================================================
 * CONSULTATION ET PLANNING DES RENDEZ-VOUS
 * ====================================================================
 * Filtres multicritères par médecin, salle, date et statut
 */

$pageTitle = "Planning des Rendez-vous";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

// Filtres
$filtreMedecin = (int)($_GET['medecin_id'] ?? 0);
$filtreSalle   = (int)($_GET['salle_id'] ?? 0);
$filtreDate    = trim($_GET['date'] ?? '');
$filtreStatut  = trim($_GET['statut'] ?? '');

$sql = "SELECT r.*, 
        p.nom AS patient_nom, p.prenom AS patient_prenom, p.telephone AS patient_tel,
        m.nom AS medecin_nom, m.prenom AS medecin_prenom, m.specialite,
        s.nom AS salle_nom
        FROM rendez_vous r
        JOIN patients p ON r.id_patient = p.id
        JOIN medecins m ON r.id_medecin = m.id
        JOIN salles s ON r.id_salle = s.id
        WHERE 1=1";
$params = [];

if ($filtreMedecin > 0) {
    $sql .= " AND r.id_medecin = ?";
    $params[] = $filtreMedecin;
}

if ($filtreSalle > 0) {
    $sql .= " AND r.id_salle = ?";
    $params[] = $filtreSalle;
}

if (!empty($filtreDate)) {
    $sql .= " AND DATE(r.date_heure) = ?";
    $params[] = $filtreDate;
}

if (!empty($filtreStatut)) {
    $sql .= " AND r.statut = ?";
    $params[] = $filtreStatut;
}

$sql .= " ORDER BY r.date_heure DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rendezVous = $stmt->fetchAll();

// Listes pour les filtres
$medecins = $pdo->query("SELECT id, nom, prenom FROM medecins ORDER BY nom ASC")->fetchAll();
$salles   = $pdo->query("SELECT id, nom FROM salles ORDER BY nom ASC")->fetchAll();

// Mini-compteurs pour l'en-tête
$statsTotal   = count($rendezVous);
$statsConfirme = count(array_filter($rendezVous, fn($r) => $r['statut'] === 'confirme'));
$statsTermine  = count(array_filter($rendezVous, fn($r) => $r['statut'] === 'termine'));
$statsAnnule   = count(array_filter($rendezVous, fn($r) => $r['statut'] === 'annule'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

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
                <div class="page-banner-title">Liste des rendez-vous</div>
                <div class="page-banner-sub">Consultez, filtrez et gérez l'ensemble des consultations médicales planifiées</div>
            </div>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-primary">
                + Nouveau rendez-vous
            </a>
        </div>
    </div>

    <!-- Mini-indicateurs de statut -->
    <div class="stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1.1rem;">📋</div>
            <div class="stat-info">
                <div class="stat-number" style="font-size: 1.4rem;"><?= $statsTotal ?></div>
                <div class="stat-label">Affichés</div>
            </div>
        </div>
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-icon green" style="width: 40px; height: 40px; font-size: 1.1rem;">✓</div>
            <div class="stat-info">
                <div class="stat-number" style="font-size: 1.4rem;"><?= $statsConfirme ?></div>
                <div class="stat-label">Confirmés</div>
            </div>
        </div>
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-icon purple" style="width: 40px; height: 40px; font-size: 1.1rem;">🩺</div>
            <div class="stat-info">
                <div class="stat-number" style="font-size: 1.4rem;"><?= $statsTermine ?></div>
                <div class="stat-label">Terminés</div>
            </div>
        </div>
        <div class="stat-card" style="padding: 14px 18px;">
            <div class="stat-icon amber" style="width: 40px; height: 40px; font-size: 1.1rem;">✕</div>
            <div class="stat-info">
                <div class="stat-number" style="font-size: 1.4rem;"><?= $statsAnnule ?></div>
                <div class="stat-label">Annulés</div>
            </div>
        </div>
    </div>

    <!-- Barre de filtres multicritères -->
    <div class="filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/rendez_vous/index.php" class="filter-grid">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Médecin :</label>
                <select name="medecin_id" class="form-control">
                    <option value="">-- Tous les médecins --</option>
                    <?php foreach ($medecins as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $filtreMedecin === (int)$m['id'] ? 'selected' : '' ?>>
                            <?= e($m['nom']) ?> <?= e($m['prenom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Salle :</label>
                <select name="salle_id" class="form-control">
                    <option value="">-- Toutes les salles --</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filtreSalle === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Date précise :</label>
                <input type="date" name="date" class="form-control" value="<?= e($filtreDate) ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Statut :</label>
                <select name="statut" class="form-control">
                    <option value="">-- Tous les statuts --</option>
                    <option value="confirme" <?= $filtreStatut === 'confirme' ? 'selected' : '' ?>>Confirmé</option>
                    <option value="en_attente" <?= $filtreStatut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="termine" <?= $filtreStatut === 'termine' ? 'selected' : '' ?>>Terminé</option>
                    <option value="annule" <?= $filtreStatut === 'annule' ? 'selected' : '' ?>>Annulé</option>
                    <option value="reporte" <?= $filtreStatut === 'reporte' ? 'selected' : '' ?>>Reporté</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    Filtrer
                </button>
                <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau moderne des rendez-vous -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date & Horaire</th>
                    <th>Patient</th>
                    <th>Médecin</th>
                    <th>Salle</th>
                    <th>Motif</th>
                    <th>Équipement requis</th>
                    <th>Statut</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rendezVous)): ?>
                    <tr>
                        <td colspan="8" class="table-empty">
                            <div class="table-empty-icon">📅</div>
                            <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                Aucun rendez-vous trouvé
                            </div>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                                Aucun enregistrement ne correspond aux filtres appliqués.
                            </p>
                            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-primary btn-sm">
                                + Programmer un rendez-vous
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rendezVous as $r): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.9rem;">
                                    <?= date('d/m/Y', strtotime($r['date_heure'])) ?>
                                </strong>
                                <div style="color: var(--primary); font-weight: 600; font-size: 0.82rem; margin-top: 2px;">
                                    <?= date('H:i', strtotime($r['date_heure'])) ?> – <?= date('H:i', strtotime($r['date_heure_fin'])) ?>
                                    <span style="color: var(--text-muted); font-weight: 400;">(<?= $r['duree_minutes'] ?>m)</span>
                                </div>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/patients/details.php?id=<?= $r['id_patient'] ?>" style="font-weight: 600; color: var(--blue-700);">
                                    <?= e($r['patient_nom']) ?> <?= e($r['patient_prenom']) ?>
                                </a>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    📞 <?= e($r['patient_tel']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    <?= e($r['medecin_nom']) ?> <?= e($r['medecin_prenom']) ?>
                                </div>
                                <span class="badge badge-primary" style="font-size: 0.7rem; margin-top: 3px;">
                                    <?= e($r['specialite']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="font-size: 0.75rem;">
                                    <?= e($r['salle_nom']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">
                                    <?= e($r['motif']) ?>
                                </div>
                                <?php if ($r['remarques']): ?>
                                    <div style="font-size: 0.77rem; color: var(--text-secondary); margin-top: 2px; max-width: 200px;">
                                        <?= e($r['remarques']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $r['equipement_requis'] ? '<span class="equip-tag">' . e($r['equipement_requis']) . '</span>' : '<span style="color: var(--gray-400); font-size: 0.8rem;">—</span>' ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'badge-secondary';
                                if ($r['statut'] === 'confirme') $badgeClass = 'badge-success';
                                elseif ($r['statut'] === 'en_attente') $badgeClass = 'badge-warning';
                                elseif ($r['statut'] === 'termine') $badgeClass = 'badge-info';
                                elseif ($r['statut'] === 'annule') $badgeClass = 'badge-danger';
                                elseif ($r['statut'] === 'reporte') $badgeClass = 'badge-warning';
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= ucfirst(e($r['statut'])) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 5px; align-items: center;">
                                    <a href="<?= BASE_URL ?>/rendez_vous/fiche.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-xs btn-outline-teal" title="Imprimer la fiche officielle du RDV">
                                        🖨️ Fiche
                                    </a>

                                    <a href="<?= BASE_URL ?>/rendez_vous/modifier.php?id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary" title="Reporter ou Modifier">
                                        ✏️ Reporter
                                    </a>

                                    <?php if ($r['statut'] !== 'termine' && $r['statut'] !== 'annule'): ?>
                                        <a href="<?= BASE_URL ?>/rendez_vous/changer_statut.php?id=<?= $r['id'] ?>&statut=termine&csrf=<?= generateCsrfToken() ?>" 
                                           class="btn btn-xs btn-success" title="Marquer comme terminé">
                                            ✓
                                        </a>
                                        <a href="<?= BASE_URL ?>/rendez_vous/changer_statut.php?id=<?= $r['id'] ?>&statut=annule&csrf=<?= generateCsrfToken() ?>" 
                                           class="btn btn-xs btn-danger btn-confirm-delete"
                                           data-confirm="Annuler ce rendez-vous médical ? Le créneau sera immédiatement libéré."
                                           title="Annuler le rendez-vous">
                                            ✕
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
