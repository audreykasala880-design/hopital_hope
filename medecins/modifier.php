<?php
/**
 * ====================================================================
 * MODIFICATION D'UN MÉDECIN
 * ====================================================================
 */

$pageTitle = "Modifier un Médecin";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('danger', "Médecin introuvable.");
    header('Location: ' . BASE_URL . '/medecins/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM medecins WHERE id = ?");
$stmt->execute([$id]);
$medecin = $stmt->fetch();
if (!$medecin) {
    flashMessage('danger', "Le médecin demandé n'existe pas.");
    header('Location: ' . BASE_URL . '/medecins/index.php');
    exit;
}

$errors = [];
$nom = $medecin['nom'];
$prenom = $medecin['prenom'];
$specialite = $medecin['specialite'];
$telephone = $medecin['telephone'];
$email = $medecin['email'];
$bureau = $medecin['bureau'];
$photo = $medecin['photo'];
$actif = (int)$medecin['actif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Erreur de sécurité CSRF : jeton invalide. Veuillez rafraîchir.";
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bureau = trim($_POST['bureau'] ?? '');
    $photo = trim($_POST['photo'] ?? $photo);
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($specialite)) $errors[] = "La spécialité est obligatoire.";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Un email valide est requis.";
    }

    if (empty($errors)) {
        $stmtChk = $pdo->prepare("SELECT id FROM medecins WHERE email = ? AND id != ?");
        $stmtChk->execute([$email, $id]);
        if ($stmtChk->fetch()) {
            $errors[] = "Un autre médecin utilise déjà cette adresse email.";
        }
    }

    if (empty($errors)) {
        try {
            $stmtUp = $pdo->prepare("UPDATE medecins 
                                     SET nom = ?, prenom = ?, specialite = ?, telephone = ?, email = ?, bureau = ?, photo = ?, actif = ?
                                     WHERE id = ?");
            $stmtUp->execute([$nom, $prenom, $specialite, $telephone, $email, $bureau, $photo, $actif, $id]);

            flashMessage('success', "Le profil du Dr. $prenom $nom a été mis à jour avec succès.");
            header('Location: ' . BASE_URL . '/medecins/index.php');
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
            <h2>✏️ Modifier le Profil : <?= e($medecin['nom']) ?> <?= e($medecin['prenom']) ?></h2>
            <p>Mise à jour des coordonnées, de la spécialité ou du statut d'activité du praticien.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-secondary btn-sm">
                ← Liste des médecins
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

            <!-- Section 1 : Identité & Spécialité -->
            <div class="form-section" style="margin-top: 0;">
                <div class="form-section-title">
                    <span>👨‍⚕️</span> 1. Identité & Spécialité
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Nom <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" value="<?= e($nom) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" value="<?= e($prenom) ?>" required>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Spécialité <span class="required">*</span></label>
                        <input type="text" name="specialite" class="form-control" value="<?= e($specialite) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Bureau / Emplacement</label>
                        <input type="text" name="bureau" class="form-control" value="<?= e($bureau) ?>">
                    </div>
                </div>
            </div>

            <!-- Section 2 : Coordonnées & Statut -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>📞</span> 2. Coordonnées & Statut Opérationnel
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Téléphone professionnel <span class="required">*</span></label>
                        <input type="text" name="telephone" class="form-control" value="<?= e($telephone) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Email professionnel <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                        <input type="checkbox" name="actif" value="1" <?= $actif ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span>Médecin actif (peut recevoir des réservations de consultations)</span>
                    </label>
                </div>
            </div>

            <!-- Section 3 : Avatar -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>🖼️</span> 3. Photo & Avatar de Profil
                </div>

                <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    <?php 
                    $avatarsDispo = [
                        'assets/images/doctor1.svg' => 'Dr Homme 1',
                        'assets/images/doctor2.svg' => 'Dr Femme 2',
                        'assets/images/doctor3.svg' => 'Dr Homme 3',
                        'assets/images/doctor4.svg' => 'Dr Femme 4',
                        'assets/images/doctor-default.svg' => 'Générique'
                    ];
                    foreach ($avatarsDispo as $path => $label): ?>
                        <label style="cursor: pointer; text-align: center; font-size: 0.8rem; background: var(--white); border: 2px solid <?= $photo === $path ? 'var(--primary)' : 'var(--border)' ?>; border-radius: var(--r-md); padding: 10px; transition: var(--t-fast);">
                            <input type="radio" name="photo" value="<?= $path ?>" <?= $photo === $path ? 'checked' : '' ?> style="margin-bottom: 6px;">
                            <img src="<?= BASE_URL ?>/<?= $path ?>" alt="<?= $label ?>" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; margin: 0 auto; display: block; border: 2px solid var(--blue-100);">
                            <span style="display: block; margin-top: 6px; font-weight: 500;"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
