<?php
/**
 * ====================================================================
 * INSCRIPTION PATIENT - Création de compte patient
 * ====================================================================
 * Page publique (accessible sans connexion).
 * Le patient remplit un formulaire avec ses informations personnelles
 * et crée son compte (email + mot de passe).
 * Un compte patient est DISTINCT du compte staff (admin/réceptionniste).
 */

$pageTitle = "Créer un compte patient";
require_once __DIR__ . '/../config/database.php';

// Si déjà connecté comme patient, rediriger vers son espace
if (isPatientLoggedIn()) {
    header('Location: ' . BASE_URL . '/patients/espace.php');
    exit;
}

$errors   = [];
$success  = false;
// Pré-remplissage en cas d'erreur (pour ne pas perdre les données saisies)
$form = [
    'prenom'        => '',
    'nom'           => '',
    'date_naissance'=> '',
    'sexe'          => '',
    'email'         => '',
    'telephone'     => '',
    'groupe_sanguin'=> '',
    'adresse'       => '',
];

// ---------------------------------------------------------------
// Traitement du formulaire (POST)
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Vérification du jeton CSRF anti-falsification
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton de sécurité invalide. Veuillez rafraîchir la page et réessayer.";
    } else {
        // 2. Récupérer et nettoyer les données du formulaire
        $form['prenom']         = trim($_POST['prenom']         ?? '');
        $form['nom']            = trim($_POST['nom']            ?? '');
        $form['date_naissance'] = trim($_POST['date_naissance'] ?? '');
        $form['sexe']           = trim($_POST['sexe']           ?? '');
        $form['email']          = strtolower(trim($_POST['email'] ?? ''));
        $form['telephone']      = trim($_POST['telephone']      ?? '');
        $form['groupe_sanguin'] = trim($_POST['groupe_sanguin'] ?? '');
        $form['adresse']        = trim($_POST['adresse']        ?? '');
        $mot_de_passe           = $_POST['mot_de_passe']        ?? '';
        $confirmation           = $_POST['confirmation']        ?? '';

        // 3. Validations obligatoires
        if (empty($form['prenom']))         $errors[] = "Le prénom est obligatoire.";
        if (empty($form['nom']))            $errors[] = "Le nom de famille est obligatoire.";
        if (empty($form['date_naissance'])) $errors[] = "La date de naissance est obligatoire.";
        if (empty($form['sexe']))           $errors[] = "Le sexe est obligatoire.";
        if (empty($form['telephone']))      $errors[] = "Le numéro de téléphone est obligatoire.";

        // 4. Validation de l'email
        if (empty($form['email'])) {
            $errors[] = "L'adresse email est obligatoire.";
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email saisie n'est pas valide.";
        }

        // 5. Validation du mot de passe
        if (strlen($mot_de_passe) < 6) {
            $errors[] = "Le mot de passe doit contenir au minimum 6 caractères.";
        } elseif ($mot_de_passe !== $confirmation) {
            $errors[] = "Les deux mots de passe ne correspondent pas.";
        }

        // 6. Vérifier que l'email n'est pas déjà utilisé
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
            $stmt->execute([$form['email']]);
            if ($stmt->fetch()) {
                $errors[] = "Cette adresse email est déjà associée à un compte patient. Veuillez vous connecter.";
            }
        }

        // 7. Tout est valide → insérer le nouveau patient en base
        if (empty($errors)) {
            // Hachage sécurisé du mot de passe (bcrypt)
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO patients 
                    (nom, prenom, date_naissance, sexe, email, telephone, groupe_sanguin, adresse, mot_de_passe, compte_actif, date_inscription)
                VALUES 
                    (:nom, :prenom, :date_naissance, :sexe, :email, :telephone, :groupe_sanguin, :adresse, :mot_de_passe, 1, NOW())
            ");
            $stmt->execute([
                ':nom'            => $form['nom'],
                ':prenom'         => $form['prenom'],
                ':date_naissance' => $form['date_naissance'],
                ':sexe'           => $form['sexe'],
                ':email'          => $form['email'],
                ':telephone'      => $form['telephone'],
                ':groupe_sanguin' => $form['groupe_sanguin'] ?: null,
                ':adresse'        => $form['adresse'] ?: null,
                ':mot_de_passe'   => $hash,
            ]);

            flashMessage('success', "Compte créé avec succès ! Bienvenue " . $form['prenom'] . " ! Vous pouvez maintenant vous connecter.");
            header('Location: ' . BASE_URL . '/patients/connexion.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#082c1e">
    <title><?= e($pageTitle) ?> - Centre Médical Hope</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230f764a'/><path d='M44 20h12v60H44zM20 44h60v12H20z' fill='white'/></svg>">
    <style>
        /* Styles spécifiques page inscription patient */
        .auth-page { min-height: 100vh; background: linear-gradient(135deg, #052e16 0%, #064e3b 40%, #065f46 100%); display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
        .auth-card { background: #fff; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); width: 100%; max-width: 640px; overflow: hidden; }
        .auth-header { background: linear-gradient(135deg, #064e3b, #059669); padding: 28px 36px; text-align: center; }
        .auth-logo { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
        .auth-title { color: #fff; font-family: 'Poppins', sans-serif; font-size: 1.4rem; font-weight: 700; margin: 0 0 4px; }
        .auth-subtitle { color: rgba(255,255,255,0.75); font-size: 0.85rem; margin: 0; }
        .auth-body { padding: 32px 36px 36px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 0.82rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-label span.req { color: #dc2626; }
        .form-control { width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 10px; font-size: 0.92rem; font-family: 'Inter', sans-serif; color: #111827; background: #f9fafb; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.15); background: #fff; }
        select.form-control { cursor: pointer; }
        .btn-block { width: 100%; display: block; }
        .btn-primary { background: linear-gradient(135deg, #059669, #047857); color: #fff; border: none; padding: 13px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: opacity .2s, transform .1s; font-family: 'Poppins', sans-serif; }
        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
        .alert { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.88rem; }
        .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }
        .alert-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
        .section-divider { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin: 24px 0 16px; display: flex; align-items: center; gap: 8px; }
        .section-divider::before, .section-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
        .auth-links { text-align: center; margin-top: 20px; font-size: 0.85rem; color: #6b7280; }
        .auth-links a { color: #059669; font-weight: 600; text-decoration: none; }
        .auth-links a:hover { text-decoration: underline; }
        .password-hint { font-size: 0.76rem; color: #9ca3af; margin-top: 4px; }
        @media (max-width: 580px) {
            .form-row { grid-template-columns: 1fr; }
            .auth-body { padding: 22px 16px 26px; }
            .auth-header { padding: 22px 16px; }
            .form-control { font-size: 16px !important; min-height: 44px; }
            .btn-primary { min-height: 44px; padding: 12px; }
            .auth-card { border-radius: 16px; }
            .auth-page { padding: 12px 10px; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- En-tête vert médical -->
        <div class="auth-header">
            <div class="auth-logo">
                <svg viewBox="0 0 40 40" width="30" height="30" fill="none">
                    <circle cx="20" cy="20" r="18" stroke="#fff" stroke-width="2"/>
                    <path d="M20 8 C14 4 7 11 7 18 C7 26 20 33 20 33 C20 33 33 26 33 18 C33 11 26 4 20 8 Z" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
                    <path d="M14 20 h4 l2 -5 l3 10 l2 -6 l2 1 h3" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
            </div>
            <div class="auth-title">Créer mon compte patient</div>
            <div class="auth-subtitle">Centre Médical Hope — Kinshasa</div>
        </div>

        <!-- Corps du formulaire -->
        <div class="auth-body">

            <!-- Erreurs de validation -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">⚠️</div>
                    <div>
                        <?php foreach ($errors as $err): ?>
                            <div><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="inscriptionForm">
                <!-- Jeton CSRF pour la sécurité -->
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <!-- === SECTION 1 : IDENTITÉ === -->
                <div class="section-divider">Informations personnelles</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="prenom">Prénom <span class="req">*</span></label>
                        <input type="text" id="prenom" name="prenom" class="form-control"
                               placeholder="Votre prénom" value="<?= e($form['prenom']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nom">Nom <span class="req">*</span></label>
                        <input type="text" id="nom" name="nom" class="form-control"
                               placeholder="Votre nom de famille" value="<?= e($form['nom']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="date_naissance">Date de naissance <span class="req">*</span></label>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-control"
                               value="<?= e($form['date_naissance']) ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sexe">Sexe <span class="req">*</span></label>
                        <select id="sexe" name="sexe" class="form-control" required>
                            <option value="">— Choisir —</option>
                            <option value="M" <?= $form['sexe'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $form['sexe'] === 'F' ? 'selected' : '' ?>>Féminin</option>
                        </select>
                    </div>
                </div>

                <!-- === SECTION 2 : CONTACT === -->
                <div class="section-divider">Coordonnées</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="telephone">Téléphone <span class="req">*</span></label>
                        <input type="tel" id="telephone" name="telephone" class="form-control"
                               placeholder="+243 XXX XXX XXX" value="<?= e($form['telephone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="groupe_sanguin">Groupe sanguin</label>
                        <select id="groupe_sanguin" name="groupe_sanguin" class="form-control">
                            <option value="">— Inconnu —</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $gs): ?>
                                <option value="<?= $gs ?>" <?= $form['groupe_sanguin'] === $gs ? 'selected' : '' ?>><?= $gs ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adresse">Adresse / Quartier</label>
                    <input type="text" id="adresse" name="adresse" class="form-control"
                           placeholder="Ex : Commune de Gombe, Kinshasa" value="<?= e($form['adresse']) ?>">
                </div>

                <!-- === SECTION 3 : COMPTE (email + mdp) === -->
                <div class="section-divider">Accès au compte</div>

                <div class="form-group">
                    <label class="form-label" for="email">Adresse email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="votre@email.com" value="<?= e($form['email']) ?>"
                           autocomplete="email" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="mot_de_passe">Mot de passe <span class="req">*</span></label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control"
                               placeholder="Min. 6 caractères" autocomplete="new-password" required>
                        <div class="password-hint">Au moins 6 caractères</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirmation">Confirmer le mot de passe <span class="req">*</span></label>
                        <input type="password" id="confirmation" name="confirmation" class="form-control"
                               placeholder="Répéter le mot de passe" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-block" style="margin-top: 8px;">
                    ✅ Créer mon compte patient
                </button>
            </form>

            <div class="auth-links">
                <p>Déjà un compte ? <a href="<?= BASE_URL ?>/patients/connexion.php">Se connecter</a></p>
                <p style="margin-top: 6px;"><a href="<?= BASE_URL ?>/index.php">← Retour à l'accueil</a></p>
            </div>

        </div><!-- /.auth-body -->
    </div><!-- /.auth-card -->
</div><!-- /.auth-page -->

</body>
</html>
