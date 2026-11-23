<?php
/**
 * ====================================================================
 * GESTION DES SALLES DE CONSULTATION
 * ====================================================================
 */

$pageTitle = "Gestion des Salles";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$statutFiltre = trim($_GET['statut'] ?? '');

$sql = "SELECT s.*,
        (SELECT COUNT(*) FROM rendez_vous r WHERE r.id_salle = s.id AND r.statut NOT IN ('annule')) AS nb_rdv_total,
        (SELECT COUNT(*) FROM rendez_vous r WHERE r.id_salle = s.id AND DATE(r.date_heure) = CURRENT_DATE() AND r.statut NOT IN ('annule')) AS nb_rdv_aujourdhui,
        (SELECT GROUP_CONCAT(CONCAT(jour_semaine, ' (', DATE_FORMAT(heure_ouverture, '%H:%i'), '-', DATE_FORMAT(heure_fermeture, '%H:%i'), ')') SEPARATOR ', ')
         FROM disponibilites_salles ds WHERE ds.id_salle = s.id) AS horaires_ouverture
        FROM salles s
        WHERE 1=1";
$params = [];

if (!empty($statutFiltre)) {
    $sql .= " AND s.statut = ?";
    $params[] = $statutFiltre;
}

$sql .= " ORDER BY s.nom ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$salles = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page Centre Médical Hope -->
    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M9 3v18"/>
                    <circle cx="6" cy="12" r="1"/>
                </svg>
            </div>
            <div>
                <div class="page-banner-title">Salles de consultation</div>
                <div class="page-banner-sub">Gestion des salles, plateaux techniques et contrôle des états de maintenance</div>
            </div>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/salles/ajouter.php" class="btn btn-primary">
                + Ajouter une salle
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/salles/index.php" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 220px;">
                <label class="form-label">Filtrer par état opérationnel :</label>
                <select name="statut" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tous les états --</option>
                    <option value="disponible" <?= $statutFiltre === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                    <option value="maintenance" <?= $statutFiltre === 'maintenance' ? 'selected' : '' ?>>En Maintenance</option>
                    <option value="fermee" <?= $statutFiltre === 'fermee' ? 'selected' : '' ?>>Fermée / Hors service</option>
                </select>
            </div>
            <?php if (!empty($statutFiltre)): ?>
                <div>
                    <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-secondary">
                        Réinitialiser
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Grille des salles sous forme de cartes 3D -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 22px; margin-bottom: 36px;">
        <?php if (empty($salles)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 48px; border: 1px solid var(--border-color); border-radius: var(--r-xl); background: #ffffff;">
                <div style="font-size: 2rem; color: var(--text-muted); margin-bottom: 12px;">🏥</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Aucune salle trouvée</h3>
                <p style="color: var(--text-muted); margin-top: 6px; font-size: 0.875rem;">Aucune salle ne correspond au statut sélectionné.</p>
            </div>
        <?php else: ?>
            <?php foreach ($salles as $s): ?>
                <div class="card card-3d" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--r-lg); padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: var(--shadow-sm); transition: all 0.25s ease;">
                    <div>
                        <!-- Header de la salle -->
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 0 0 4px;">
                                    <?= e($s['nom']) ?>
                                </h3>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    Étage <?= e($s['etage']) ?> &bull; Capacité <?= $s['capacite'] ?> pers.
                                </div>
                            </div>
                            <?php if ($s['statut'] === 'disponible'): ?>
                                <span class="status-pill status-confirmed">Disponible</span>
                            <?php elseif ($s['statut'] === 'maintenance'): ?>
                                <span class="status-pill status-pending">Maintenance</span>
                            <?php else: ?>
                                <span class="status-pill status-cancelled">Fermée</span>
                            <?php endif; ?>
                        </div>

                        <!-- Équipements -->
                        <div style="margin: 14px 0 12px;">
                            <span style="display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 8px;">Équipements médicaux :</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <?php 
                                $eqs = explode(',', $s['equipements']);
                                foreach ($eqs as $eq): 
                                    if (trim($eq) === '') continue;
                                ?>
                                    <span style="display: inline-block; background-color: #f1f5f9; color: #334155; font-size: 0.75rem; font-weight: 500; padding: 3px 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                        <?= e(trim($eq)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Statistiques consultations -->
                        <div style="background-color: #f8fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 10px 12px; font-size: 0.775rem; color: var(--text-muted); margin-top: 14px;">
                            <span>Aujourd'hui : <strong style="color: var(--text-main);"><?= $s['nb_rdv_aujourdhui'] ?></strong> consultation(s)</span>
                            <span style="margin: 0 8px;">&bull;</span>
                            <span>Total : <strong style="color: var(--text-main);"><?= $s['nb_rdv_total'] ?></strong></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; border-top: 1px solid #edf2f7; padding-top: 12px;">
                        <a href="<?= BASE_URL ?>/salles/modifier.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 0.8rem; padding: 6px 12px;">
                            Modifier
                        </a>
                        <a href="<?= BASE_URL ?>/salles/supprimer.php?id=<?= $s['id'] ?>" 
                           class="btn btn-secondary btn-sm btn-confirm-delete"
                           style="font-size: 0.8rem; padding: 6px 12px;"
                           data-confirm="Supprimer cette salle ? Si des rendez-vous y sont associés, la suppression sera bloquée.">
                            Supprimer
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
