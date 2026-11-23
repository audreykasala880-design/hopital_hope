<?php
/**
 * ====================================================================
 * GESTION DES PATIENTS : LISTE ET RECHERCHE
 * ====================================================================
 */

$pageTitle = "Gestion des Patients";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$search = trim($_GET['search'] ?? '');
$sexe   = trim($_GET['sexe'] ?? '');

$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM rendez_vous r WHERE r.id_patient = p.id) AS total_rdv,
        (SELECT MAX(date_heure) FROM rendez_vous r WHERE r.id_patient = p.id) AS dernier_rdv
        FROM patients p 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.nom LIKE ? OR p.prenom LIKE ? OR p.telephone LIKE ? OR p.email LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if (!empty($sexe)) {
    $sql .= " AND p.sexe = ?";
    $params[] = $sexe;
}

$sql .= " ORDER BY p.nom ASC, p.prenom ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$totalPatients = count($patients);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- En-tête de page moderne Centre Médical Hope -->
    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <div class="page-banner-title">Patients</div>
                <div class="page-banner-sub">Gestion des dossiers médicaux, inscriptions et historique des consultations</div>
            </div>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/patients/ajouter.php" class="btn btn-primary">
                + Inscrire un patient
            </a>
        </div>
    </div>

    <!-- Barre de recherche -->
    <div class="filter-bar">
        <form method="GET" action="<?= BASE_URL ?>/patients/index.php" class="filter-grid">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Rechercher un dossier :</label>
                <input type="text" name="search" class="form-control" placeholder="Nom, prénom, téléphone, email..." value="<?= e($search) ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Genre :</label>
                <select name="sexe" class="form-control">
                    <option value="">-- Tous les genres --</option>
                    <option value="M" <?= $sexe === 'M' ? 'selected' : '' ?>>Masculin (M)</option>
                    <option value="F" <?= $sexe === 'F' ? 'selected' : '' ?>>Féminin (F)</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Rechercher</button>
                <a href="<?= BASE_URL ?>/patients/index.php" class="btn btn-secondary">Effacer</a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Dossier #</th>
                    <th>Nom & Prénom</th>
                    <th>Âge / Naissance</th>
                    <th>Genre</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Groupe Sanguin</th>
                    <th>Historique RDV</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="9" class="table-empty">
                            <div class="table-empty-icon">👥</div>
                            <div style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                Aucun dossier patient trouvé
                            </div>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
                                Aucun dossier ne correspond à vos critères de recherche.
                            </p>
                            <a href="<?= BASE_URL ?>/patients/ajouter.php" class="btn btn-primary btn-sm">
                                + Inscrire le premier patient
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $p): 
                        $birthDate = new DateTime($p['date_naissance']);
                        $age = $birthDate->diff(new DateTime())->y;
                    ?>
                        <tr>
                            <td>
                                <span class="badge badge-secondary" style="font-family: monospace;">
                                    #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/patients/details.php?id=<?= $p['id'] ?>" style="font-weight: 700; color: var(--blue-700);">
                                    <?= e($p['nom']) ?> <?= e($p['prenom']) ?>
                                </a>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary);"><?= $age ?> ans</strong>
                                <div style="font-size: 0.775rem; color: var(--text-muted);">
                                    <?= date('d/m/Y', strtotime($p['date_naissance'])) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $p['sexe'] === 'M' ? 'badge-info' : 'badge-primary' ?>">
                                    <?= $p['sexe'] === 'M' ? 'Homme' : 'Femme' ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.85rem;"><?= e($p['telephone']) ?></strong>
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; color: var(--text-secondary);"><?= e($p['email'] ?: '—') ?></span>
                            </td>
                            <td>
                                <?php if ($p['groupe_sanguin']): ?>
                                    <span class="badge badge-danger"><?= e($p['groupe_sanguin']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 0.8rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-teal">
                                    <?= $p['total_rdv'] ?> consultation<?= $p['total_rdv'] > 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/patients/details.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-outline-primary" title="Voir Dossier">
                                        👁️ Dossier
                                    </a>
                                    <a href="<?= BASE_URL ?>/patients/modifier.php?id=<?= $p['id'] ?>" class="btn btn-xs btn-secondary" title="Modifier">
                                        ✏️
                                    </a>
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
