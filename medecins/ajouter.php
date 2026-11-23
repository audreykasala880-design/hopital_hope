<?php
/**
 * ====================================================================
 * AJOUT D'UN NOUVEAU MÉDECIN
 * ====================================================================
 */

$pageTitle = "Ajouter un Médecin";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$errors = [];
$nom = '';
$prenom = '';
$specialite = '';
$telephone = '';
$email = '';
$bureau = '';
$photo = 'assets/images/doctor-default.png';
$joursSelectionnes = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
$heureDebut = '08:00';
$heureFin = '16:00';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Erreur de sécurité CSRF : jeton de session invalide. Veuillez rafraîchir.";
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bureau = trim($_POST['bureau'] ?? '');
    $photo = trim($_POST['photo'] ?? 'assets/images/doctor-default.png');
    $joursSelectionnes = $_POST['jours'] ?? [];
    $heureDebut = $_POST['heure_debut'] ?? '08:00';
    $heureFin = $_POST['heure_fin'] ?? '16:00';

    if (empty($nom)) $errors[] = "Le nom du médecin est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom du médecin est obligatoire.";
    if (empty($specialite)) $errors[] = "La spécialité est obligatoire.";
    if (empty($telephone)) $errors[] = "Le numéro de téléphone est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Une adresse email valide est obligatoire.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM medecins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Un médecin avec cette adresse email existe déjà.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO medecins (nom, prenom, specialite, telephone, email, bureau, photo, actif) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nom, $prenom, $specialite, $telephone, $email, $bureau, $photo]);
            $medecinId = $pdo->lastInsertId();

            if (!empty($joursSelectionnes) && !empty($heureDebut) && !empty($heureFin)) {
                $stmtDisp = $pdo->prepare("INSERT INTO disponibilites_medecins (id_medecin, jour_semaine, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");
                foreach ($joursSelectionnes as $jour) {
                    $stmtDisp->execute([$medecinId, $jour, $heureDebut . ':00', $heureFin . ':00']);
                }
            }

            $pdo->commit();
            flashMessage('success', "Le médecin $prenom $nom a été ajouté avec succès.");
            header('Location: ' . BASE_URL . '/medecins/index.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
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
            <h2>➕ Nouveau Praticien Médical</h2>
            <p>Ajoutez un praticien, définissez sa spécialité et programmez ses horaires réguliers de consultation.</p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-secondary btn-sm">
                ← Corps médical
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
                        <input type="text" name="nom" class="form-control" placeholder="Ex: Dr. Ilunga" value="<?= e($nom) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" placeholder="Ex: Alain" value="<?= e($prenom) ?>" required>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Discipline / Spécialité <span class="required">*</span></label>
                        <input type="text" name="specialite" class="form-control" placeholder="Ex: Cardiologie, Pédiatrie, Gynécologie..." value="<?= e($specialite) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Bureau / Cabinet de consultation</label>
                        <input type="text" name="bureau" class="form-control" placeholder="Ex: Bureau C-101" value="<?= e($bureau) ?>">
                    </div>
                </div>
            </div>

            <!-- Section 2 : Contact -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>📞</span> 2. Coordonnées Professionnelles
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Téléphone professionnel <span class="required">*</span></label>
                        <input type="text" name="telephone" class="form-control" placeholder="Ex: +243 810 000 001" value="<?= e($telephone) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Email professionnel <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="Ex: alain.ilunga@hopecmedical.cd" value="<?= e($email) ?>" required>
                    </div>
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

            <!-- Section 4 : Horaires de travail -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>⏰</span> 4. Horaires de Consultation Habituels
                </div>

                <p style="font-size: 0.825rem; color: var(--text-secondary); margin-bottom: 14px;">Sélectionnez les jours et la plage horaire régulière où ce médecin consulte :</p>
                
                <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 16px;">
                    <?php 
                    $tousJours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    foreach ($tousJours as $j): ?>
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                            <input type="checkbox" name="jours[]" value="<?= $j ?>" <?= in_array($j, $joursSelectionnes) ? 'checked' : '' ?>>
                            <?= $j ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Heure de début :</label>
                        <input type="time" name="heure_debut" class="form-control" value="<?= e($heureDebut) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Heure de fin :</label>
                        <input type="time" name="heure_fin" class="form-control" value="<?= e($heureFin) ?>">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/medecins/index.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Enregistrer le Médecin
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
