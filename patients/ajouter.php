<?php
/**
 * ====================================================================
 * ENREGISTREMENT D'UN NOUVEAU PATIENT
 * ====================================================================
 */

$pageTitle = "Inscrire un Patient";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$errors = [];
$nom = '';
$prenom = '';
$date_naissance = '';
$sexe = 'M';
$telephone = '';
$email = '';
$adresse = '';
$groupe_sanguin = '';
$antecedents = '';

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

    if (empty($nom)) $errors[] = "Le nom du patient est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom du patient est obligatoire.";
    if (empty($date_naissance)) $errors[] = "La date de naissance est obligatoire.";
    if (empty($telephone)) $errors[] = "Le numéro de téléphone est obligatoire.";
    if (!in_array($sexe, ['M', 'F'])) $errors[] = "Le genre sélectionné est invalide.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO patients (nom, prenom, date_naissance, sexe, telephone, email, adresse, groupe_sanguin, antecedents)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nom, $prenom, $date_naissance, $sexe, $telephone,
                $email ?: null, $adresse ?: null, $groupe_sanguin ?: null, $antecedents ?: null
            ]);
            $patientId = $pdo->lastInsertId();

            flashMessage('success', "Le patient $prenom $nom a été inscrit avec succès (Dossier #$patientId).");
            
            if (isset($_GET['redirect_rdv'])) {
                header('Location: ' . BASE_URL . '/rendez_vous/prendre.php?patient_id=' . $patientId);
            } else {
                header('Location: ' . BASE_URL . '/patients/details.php?id=' . $patientId);
            }
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur SQL lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 860px;">

    <!-- En-tête de page -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>📝 Inscription d'un Nouveau Patient</h2>
            <p>Créez le dossier médical et administratif du patient pour autoriser la prise de rendez-vous.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/patients/index.php" class="btn btn-secondary btn-sm">
                ← Liste des patients
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

            <!-- Section 1 : État civil -->
            <div class="form-section" style="margin-top: 0;">
                <div class="form-section-title">
                    <span>👤</span> 1. État Civil & Identité
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Nom de famille <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" placeholder="Ex: Mukendi" value="<?= e($nom) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" placeholder="Ex: Christian" value="<?= e($prenom) ?>" required>
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

            <!-- Section 2 : Contact -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>📞</span> 2. Coordonnées & Localisation
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Numéro de téléphone <span class="required">*</span></label>
                        <input type="text" name="telephone" class="form-control" placeholder="Ex: +243 815 000 001" value="<?= e($telephone) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Adresse Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Ex: christian.mukendi@email.com" value="<?= e($email) ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px; margin-bottom: 0;">
                    <label class="form-label">Adresse de résidence complète</label>
                    <input type="text" name="adresse" class="form-control" placeholder="Ex: Q/ Limete, Av. Université No 14, Kinshasa" value="<?= e($adresse) ?>">
                </div>
            </div>

            <!-- Section 3 : Santé & Antécédents -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>🩺</span> 3. Informations Médicales & Antécédents
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
                    <label class="form-label">Antécédents médicaux / Allergies / Traitements en cours</label>
                    <textarea name="antecedents" class="form-control" style="min-height: 90px;" placeholder="Ex: Allergie à la pénicilline, asthmatique, hypertension modérée..."><?= e($antecedents) ?></textarea>
                    <span class="form-hint">Ces données seront affichées sur la fiche du praticien lors de la consultation.</span>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/patients/index.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer le Dossier Patient
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
