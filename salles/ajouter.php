<?php
/**
 * ====================================================================
 * AJOUT D'UNE SALLE DE CONSULTATION
 * ====================================================================
 */

$pageTitle = "Ajouter une Salle";
require_once __DIR__ . '/../config/database.php';
requireLogin(); // Accès restreint au personnel médical / administratif

$errors = [];
$nom = '';
$etage = 'Rez-de-chaussée';
$capacite = 1;
$equipements = 'Table d examen, Tensiomètre, Stéthoscope';
$statut = 'disponible';
$description = '';
$heureOuverture = '07:30';
$heureFermeture = '18:30';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton de sécurité invalide. Veuillez rafraîchir la page.";
    }

    $nom = trim($_POST['nom'] ?? '');
    $etage = trim($_POST['etage'] ?? '');
    $capacite = (int)($_POST['capacite'] ?? 1);
    $equipements = trim($_POST['equipements'] ?? '');
    $statut = trim($_POST['statut'] ?? 'disponible');
    $description = trim($_POST['description'] ?? '');
    $heureOuverture = $_POST['heure_ouverture'] ?? '07:30';
    $heureFermeture = $_POST['heure_fermeture'] ?? '18:30';

    if (empty($nom)) $errors[] = "Le nom de la salle est obligatoire.";
    if (empty($etage)) $errors[] = "L'étage est obligatoire.";
    if ($capacite < 1) $errors[] = "La capacité doit être d'au moins 1 personne.";
    if (empty($equipements)) $errors[] = "Veuillez spécifier au moins un équipement.";

    if (empty($errors)) {
        $stmtChk = $pdo->prepare("SELECT id FROM salles WHERE nom = ?");
        $stmtChk->execute([$nom]);
        if ($stmtChk->fetch()) {
            $errors[] = "Une salle avec ce nom existe déjà.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO salles (nom, etage, capacite, equipements, statut, description) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $etage, $capacite, $equipements, $statut, $description]);
            $salleId = $pdo->lastInsertId();

            // Horaires d'ouverture hebdomadaires par défaut (Lundi à Samedi)
            $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $stmtDisp = $pdo->prepare("INSERT INTO disponibilites_salles (id_salle, jour_semaine, heure_ouverture, heure_fermeture) VALUES (?, ?, ?, ?)");
            foreach ($jours as $j) {
                $stmtDisp->execute([$salleId, $j, $heureOuverture . ':00', $heureFermeture . ':00']);
            }

            $pdo->commit();
            flashMessage('success', "La salle $nom a été créée avec succès.");
            header('Location: ' . BASE_URL . '/salles/index.php');
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
            <h2>➕ Ajouter une Salle de Consultation</h2>
            <p>Configurez un nouveau local de consultation, ses équipements et ses plages d'ouverture.</p>
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
                    <span>🏥</span> 1. Caractéristiques de la Salle
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Nom / Numéro de la salle <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" placeholder="Ex: Salle 105 - Ophtalmologie" value="<?= e($nom) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Étage / Emplacement <span class="required">*</span></label>
                        <input type="text" name="etage" class="form-control" placeholder="Ex: Rez-de-chaussée, 1er Étage..." value="<?= e($etage) ?>" required>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Capacité d'accueil <span class="required">*</span></label>
                        <input type="number" name="capacite" class="form-control" min="1" max="10" value="<?= $capacite ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Statut initial <span class="required">*</span></label>
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
                    <span>🩺</span> 2. Équipements Médicaux Installés
                </div>

                <div class="form-group">
                    <label class="form-label">Liste des équipements (séparés par des virgules) <span class="required">*</span></label>
                    <input type="text" name="equipements" class="form-control" placeholder="Ex: ECG, Tensiomètre, Table d examen, Échographe" value="<?= e($equipements) ?>" required>
                    <span class="form-hint">Important : Le moteur de réservation compare cette liste avec les équipements requis par le médecin.</span>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Description ou observations techniques</label>
                    <textarea name="description" class="form-control" style="min-height: 80px;" placeholder="Notes techniques, dimensions, spécificités d'accès..."><?= e($description) ?></textarea>
                </div>
            </div>

            <!-- Section 3 : Horaires -->
            <div class="form-section">
                <div class="form-section-title">
                    <span>⏰</span> 3. Plages d'Accès Quotidiennes (Lun – Sam)
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Heure d'ouverture :</label>
                        <input type="time" name="heure_ouverture" class="form-control" value="<?= e($heureOuverture) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Heure de fermeture :</label>
                        <input type="time" name="heure_fermeture" class="form-control" value="<?= e($heureFermeture) ?>">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/salles/index.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    💾 Créer et Enregistrer la Salle
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
