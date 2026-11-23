<?php
/**
 * ====================================================================
 * MODIFICATION D'UNE SALLE
 * ====================================================================
 */

$pageTitle = "Modifier une Salle";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Salle introuvable.");
    header('Location: ' . BASE_URL . '/salles/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM salles WHERE id = ?");
$stmt->execute([$id]);
$salle = $stmt->fetch();
if (!$salle) {
    flashMessage('danger', "Cette salle n'existe pas.");
    header('Location: ' . BASE_URL . '/salles/index.php');
    exit;
}

$errors = [];
$nom = $salle['nom'];
$etage = $salle['etage'];
$capacite = $salle['capacite'];
$equipements = $salle['equipements'];
$statut = $salle['statut'];
$description = $salle['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton de sécurité CSRF invalide. Veuillez rafraîchir la page.";
    }

    $nom = trim($_POST['nom'] ?? '');
    $etage = trim($_POST['etage'] ?? '');
    $capacite = (int)($_POST['capacite'] ?? 1);
    $equipements = trim($_POST['equipements'] ?? '');
    $statut = trim($_POST['statut'] ?? 'disponible');
    $description = trim($_POST['description'] ?? '');

    if (empty($nom)) $errors[] = "Le nom de la salle est obligatoire.";
    if (empty($etage)) $errors[] = "L'étage est obligatoire.";
    if ($capacite < 1) $errors[] = "La capacité minimale est de 1.";
    if (empty($equipements)) $errors[] = "Veuillez renseigner les équipements.";

    if (empty($errors)) {
        $stmtChk = $pdo->prepare("SELECT id FROM salles WHERE nom = ? AND id != ?");
        $stmtChk->execute([$nom, $id]);
        if ($stmtChk->fetch()) {
            $errors[] = "Une autre salle porte déjà ce nom.";
        }
    }

    if (empty($errors)) {
        try {
            $stmtUp = $pdo->prepare("UPDATE salles 
                                     SET nom = ?, etage = ?, capacite = ?, equipements = ?, statut = ?, description = ?
                                     WHERE id = ?");
            $stmtUp->execute([$nom, $etage, $capacite, $equipements, $statut, $description, $id]);

            flashMessage('success', "Les informations de la salle $nom ont été mises à jour avec succès.");
            header('Location: ' . BASE_URL . '/salles/index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur SQL : " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 860px;">

    <!-- En-tête de page -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>✏️ Modifier la Salle : <?= e($salle['nom']) ?></h2>
            <p>Mettez à jour les équipements, la capacité ou l'état de maintenance.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-secondary btn-sm">
                ← Liste des salles
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <div class="alert-icon">✕</div>
            <div class="alert-content">
                <strong>Erreurs de validation :</strong>
                <ul style="margin: 6px 0 0 16px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" style="box-shadow: var(--s-md);">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- Section 1 : Caractéristiques -->
            <div class="form-section" style="margin-top: 0;">
                <div class="form-section-title">
                    <span>🏥</span> 1. Caractéristiques Générales
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Nom de la salle <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" value="<?= e($nom) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Étage <span class="required">*</span></label>
                        <input type="text" name="etage" class="form-control" value="<?= e($etage) ?>" required>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Capacité d'accueil <span class="required">*</span></label>
                        <input type="number" name="capacite" class="form-control" min="1" max="10" value="<?= $capacite ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Statut opérationnel <span class="required">*</span></label>
                        <select name="statut" class="form-control" required>
                            <option value="disponible" <?= $statut === 'disponible' ? 'selected' : '' ?>>Disponible (Opérationnelle)</option>
                            <option value="maintenance" <?= $statut === 'maintenance' ? 'selected' : '' ?>>En Maintenance technique</option>
                            <option value="fermee" <?= $statut === 'fermee' ? 'selected' : '' ?>>Fermée / Hors service</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2 : Équipements -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>🩺</span> 2. Équipements & Description
                </div>

                <div class="form-group">
                    <label class="form-label">Équipements (séparés par des virgules) <span class="required">*</span></label>
                    <input type="text" name="equipements" class="form-control" value="<?= e($equipements) ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Description ou observations techniques</label>
                    <textarea name="description" class="form-control" style="min-height: 80px;"><?= e($description) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
