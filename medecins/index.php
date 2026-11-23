<?php
/**
 * ====================================================================
 * GESTION DES MÉDECINS : LISTE ET CONSULTATION
 * ====================================================================
 */

$pageTitle = "Gestion des Médecins";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$search = trim($_GET['search'] ?? '');
$specialiteFiltre = trim($_GET['specialite'] ?? '');

$sql = "SELECT m.*, 
        (SELECT COUNT(*) FROM rendez_vous r WHERE r.id_medecin = m.id AND r.statut != 'annule') AS nb_rdv,
        (SELECT GROUP_CONCAT(CONCAT(jour_semaine, ' (', DATE_FORMAT(heure_debut, '%H:%i'), '-', DATE_FORMAT(heure_fin, '%H:%i'), ')') SEPARATOR ', ') 
         FROM disponibilites_medecins dm WHERE dm.id_medecin = m.id) AS horaires
        FROM medecins m 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (m.nom LIKE ? OR m.prenom LIKE ? OR m.email LIKE ? OR m.specialite LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if (!empty($specialiteFiltre)) {
    $sql .= " AND m.specialite = ?";
    $params[] = $specialiteFiltre;
}

$sql .= " ORDER BY m.nom ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medecins = $stmt->fetchAll();

// Liste des spécialités distinctes pour le filtre
$specialites = $pdo->query("SELECT DISTINCT specialite FROM medecins ORDER BY specialite ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page moderne Centre Médical Hope -->
    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <div class="page-banner-title">Médecins</div>
                <div class="page-banner-sub">Une équipe de professionnels de santé hautement qualifiés à votre service</div>
            </div>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/medecins/ajouter.php" class="btn btn-primary">
                + Ajouter un médecin
            </a>
        </div>
    </div>

    <!-- Barre de recherche et filtre -->
    <div class="filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/medecins/index.php" class="filter-grid">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Rechercher un médecin :</label>
                <input type="text" name="search" class="form-control" placeholder="Nom, prénom, email, spécialité..." value="<?= e($search) ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Filtrer par spécialité :</label>
                <select name="specialite" class="form-control">
                    <option value="">-- Toutes les spécialités --</option>
                    <?php foreach ($specialites as $spec): ?>
                        <option value="<?= e($spec) ?>" <?= $specialiteFiltre === $spec ? 'selected' : '' ?>>
                            <?= e($spec) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filtrer</button>
                <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Grille moderne de cartes praticiens (style mockup Centre Médical Hope) -->
    <?php if (empty($medecins)): ?>
        <div class="card" style="text-align: center; padding: 48px; border: 1px solid var(--border-color); border-radius: var(--r-xl); background: #ffffff;">
            <div style="font-size: 2rem; color: var(--text-muted); margin-bottom: 12px;">👨‍⚕️</div>
            <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">
                Aucun médecin trouvé
            </div>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 20px;">
                Aucun praticien ne correspond à vos critères de recherche.
            </p>
            <a href="<?= BASE_URL ?>/medecins/ajouter.php" class="btn btn-primary btn-sm">
                + Enregistrer un médecin
            </a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 32px;">
            <?php foreach ($medecins as $m): ?>
                <div class="card card-3d" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--r-lg); padding: 22px; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.25s ease; box-shadow: var(--shadow-sm);">
                    
                    <div>
                        <!-- Header médecin : Avatar + Nom + Spécialité -->
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                            <img src="<?= BASE_URL ?>/<?= e($m['photo']) ?>" alt="<?= e($m['nom']) ?>" 
                                 style="width: 58px; height: 58px; border-radius: 50%; object-fit: cover; border: 2.5px solid #ccebdb; background: #edf9f2;"
                                 onerror="this.src='<?= BASE_URL ?>/assets/images/doctor-default.svg'">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                    <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-main); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        Dr. <?= e($m['nom']) ?> <?= e($m['prenom']) ?>
                                    </h3>
                                    <?php if ($m['actif']): ?>
                                        <span class="status-pill status-confirmed" style="font-size: 0.7rem; padding: 2px 8px;">Actif</span>
                                    <?php else: ?>
                                        <span class="status-pill status-cancelled" style="font-size: 0.7rem; padding: 2px 8px;">Inactif</span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 4px;">
                                    <span style="display: inline-block; background-color: #e3f5eb; color: #095c3b; font-size: 0.775rem; font-weight: 600; padding: 3px 10px; border-radius: 12px;">
                                        <?= e($m['specialite']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Détails : Horaires, Bureau, Contact -->
                        <div style="background-color: #f8fafc; border: 1px solid #edf2f7; border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 0.825rem; line-height: 1.5;">
                            <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; color: var(--text-main);">
                                <span style="color: var(--teal-500); font-weight: 600;">🕒</span>
                                <div>
                                    <strong>Horaires :</strong> 
                                    <span style="color: var(--text-muted);"><?= $m['horaires'] ? e($m['horaires']) : 'Non configurés' ?></span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; color: var(--text-main);">
                                <span style="color: var(--teal-500); font-weight: 600;">📍</span>
                                <div>
                                    <strong>Bureau :</strong> 
                                    <span style="color: var(--text-muted);"><?= e($m['bureau'] ?? 'Consultations') ?></span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--text-main);">
                                <span style="color: var(--teal-500); font-weight: 600;">📞</span>
                                <div>
                                    <strong>Contact :</strong> 
                                    <span style="color: var(--text-muted);"><?= e($m['telephone']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #edf2f7; padding-top: 14px; margin-top: 4px;">
                        <a href="<?= BASE_URL ?>/rendez_vous/prendre.php?medecin_id=<?= $m['id'] ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; justify-content: center; margin-right: 8px; font-size: 0.8rem;">
                            Prendre RDV
                        </a>
                        <a href="<?= BASE_URL ?>/medecins/modifier.php?id=<?= $m['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 6px 10px; margin-right: 6px;" title="Modifier les informations">
                            Modifier
                        </a>
                        <a href="<?= BASE_URL ?>/medecins/supprimer.php?id=<?= $m['id'] ?>" 
                           class="btn btn-secondary btn-sm btn-confirm-delete" 
                           style="padding: 6px 10px;" 
                           data-confirm="Voulez-vous supprimer ce médecin ? S'il a des consultations dans l'historique, la suppression sera bloquée pour préserver l'intégrité."
                           title="Supprimer">
                            Supprimer
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
