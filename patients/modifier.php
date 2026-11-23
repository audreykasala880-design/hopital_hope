<?php
/**
 * ====================================================================
 * MODIFICATION DU DOSSIER D'UN PATIENT
 * ====================================================================
 */

$pageTitle = "Modifier un Dossier Patient";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Patient introuvable.");
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

$errors = [];
$nom = $patient['nom'];
$prenom = $patient['prenom'];
$date_naissance = $patient['date_naissance'];
$sexe = $patient['sexe'];
$telephone = $patient['telephone'];
$email = $patient['email'];
$adresse = $patient['adresse'];
$groupe_sanguin = $patient['groupe_sanguin'];
$antecedents = $patient['antecedents'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton de sécurité invalide. Veuillez rafraîchir la page.";
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $sexe = trim($_POST['sexe'] ?? 'M');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $groupe_sanguin = trim($_POST['groupe_sanguin'] ?? '');
    $antecedents = trim($_POST['antecedents'] ?? '');

    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($date_naissance)) $errors[] = "La date de naissance est obligatoire.";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire.";

    if (empty($errors)) {
        try {
            $stmtUp = $pdo->prepare("UPDATE patients 
                                     SET nom = ?, prenom = ?, date_naissance = ?, sexe = ?, telephone = ?,
                                         email = ?, adresse = ?, groupe_sanguin = ?, antecedents = ?
                                     WHERE id = ?");
            $stmtUp->execute([
                $nom, $prenom, $date_naissance, $sexe, $telephone,
                $email ?: null, $adresse ?: null, $groupe_sanguin ?: null, $antecedents ?: null, $id
            ]);

            flashMessage('success', "Le dossier du patient $prenom $nom a été mis à jour avec succès.");
            header('Location: ' . BASE_URL . '/patients/details.php?id=' . $id);
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
            <h2>✏️ Modifier le Dossier : <?= e($patient['nom']) ?> <?= e($patient['prenom']) ?></h2>
            <p>Mise à jour des coordonnées et antécédents médicaux du patient.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/patients/details.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">
                ← Retour au dossier
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <div class="alert-icon">✕</div>
            <div class="alert-content">
                <strong>Erreurs de saisie :</strong>
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

            <!-- Section 1 : État civil -->
            <div class="form-section" style="margin-top: 0;">
                <div class="form-section-title">
                    <span>👤</span> 1. Identité & État Civil
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Nom de famille <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" value="<?= e($nom) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" value="<?= e($prenom) ?>" required>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Date de naissance <span class="required">*</span></label>
                        <input type="date" name="date_naissance" class="form-control" value="<?= e($date_naissance) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Genre / Sexe <span class="required">*</span></label>
                        <select name="sexe" class="form-control" required>
                            <option value="M" <?= $sexe === 'M' ? 'selected' : '' ?>>Masculin (M)</option>
                            <option value="F" <?= $sexe === 'F' ? 'selected' : '' ?>>Féminin (F)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2 : Coordonnées -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>📞</span> 2. Coordonnées de Contact
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Numéro de téléphone <span class="required">*</span></label>
                        <input type="text" name="telephone" class="form-control" value="<?= e($telephone) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Adresse Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px; margin-bottom: 0;">
                    <label class="form-label">Adresse de résidence</label>
                    <input type="text" name="adresse" class="form-control" value="<?= e($adresse) ?>">
                </div>
            </div>

            <!-- Section 3 : Santé -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>🩺</span> 3. Données Médicales
                </div>

                <div class="form-group">
                    <label class="form-label">Groupe Sanguin</label>
                    <select name="groupe_sanguin" class="form-control" style="max-width: 260px;">
                        <option value="">-- Non déterminé --</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $gs): ?>
                            <option value="<?= $gs ?>" <?= $groupe_sanguin === $gs ? 'selected' : '' ?>><?= $gs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Antécédents médicaux / Allergies</label>
                    <textarea name="antecedents" class="form-control" style="min-height: 90px;"><?= e($antecedents) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/patients/details.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
