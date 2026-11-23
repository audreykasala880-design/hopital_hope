<?php
/**
 * ====================================================================
 * CONNEXION PATIENT - Espace Patient
 * ====================================================================
 * Page publique pour permettre aux patients de se connecter à leur espace.
 * Gère une session dédiée ($_SESSION['patient_id']) indépendante du staff.
 */

$pageTitle = "Connexion Patient";
require_once __DIR__ . '/../config/database.php';

// Si déjà connecté comme patient, redirection directe vers son espace
if (isPatientLoggedIn()) {
    header('Location: ' . BASE_URL . '/patients/espace.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du jeton CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton de sécurité invalide. Veuillez rafraîchir la page et réessayer.";
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['mot_de_passe'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Veuillez renseigner votre email et votre mot de passe.";
        } else {
            // Recherche du patient par son adresse email
            $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mot_de_passe, compte_actif FROM patients WHERE email = ?");
            $stmt->execute([$email]);
            $patient = $stmt->fetch();

            if ($patient && !empty($patient['mot_de_passe']) && password_verify($password, $patient['mot_de_passe'])) {
                if (isset($patient['compte_actif']) && (int)$patient['compte_actif'] === 0) {
                    $errors[] = "Votre compte est désactivé. Veuillez contacter l'accueil de la clinique.";
                } else {
                    // Initialiser la session spécifique au patient
                    session_regenerate_id(true);
                    $_SESSION['patient_id']     = $patient['id'];
                    $_SESSION['patient_nom']    = $patient['nom'];
                    $_SESSION['patient_prenom'] = $patient['prenom'];
                    $_SESSION['patient_email']  = $patient['email'];

                    flashMessage('success', "Bienvenue, " . $patient['prenom'] . " " . $patient['nom'] . " ! Vous êtes connecté à votre espace patient.");
                    
                    // Si une redirection demandée (ex: prise de rdv en attente)
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $target = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $target);
                    } else {
                        header('Location: ' . BASE_URL . '/patients/espace.php');
                    }
                    exit;
                }
            } else {
                $errors[] = "Identifiants invalides. Vérifiez votre email et mot de passe ou créez un compte.";
            }
        }
    }
}

$flashMessages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Centre Médical Hope</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230f764a'/><path d='M44 20h12v60H44zM20 44h60v12H20z' fill='white'/></svg>">
    <style>
        .auth-page { min-height: 100vh; background: linear-gradient(135deg, #052e16 0%, #064e3b 40%, #065f46 100%); display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
        .auth-card { background: #fff; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); width: 100%; max-width: 460px; overflow: hidden; }
        .auth-header { background: linear-gradient(135deg, #064e3b, #059669); padding: 28px 32px; text-align: center; }
        .auth-logo { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
        .auth-title { color: #fff; font-family: 'Poppins', sans-serif; font-size: 1.35rem; font-weight: 700; margin: 0 0 4px; }
        .auth-subtitle { color: rgba(255,255,255,0.8); font-size: 0.85rem; margin: 0; }
        .auth-body { padding: 30px 32px 32px; }
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 0.82rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 11px 14px; border: 1.5px solid #d1d5db; border-radius: 10px; font-size: 0.92rem; font-family: 'Inter', sans-serif; color: #111827; background: #f9fafb; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,0.15); background: #fff; }
        .btn-block { width: 100%; display: block; }
        .btn-primary { background: linear-gradient(135deg, #059669, #047857); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: opacity .2s, transform .1s; font-family: 'Poppins', sans-serif; }
        .btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
        .alert { display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; }
        .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }
        .alert-success { background: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; }
        .auth-links { text-align: center; margin-top: 22px; font-size: 0.85rem; color: #6b7280; }
        .auth-links a { color: #059669; font-weight: 600; text-decoration: none; }
        .auth-links a:hover { text-decoration: underline; }
        .switch-box { margin-top: 20px; padding-top: 18px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 0.82rem; color: #6b7280; }
        .switch-box a { color: #064e3b; font-weight: 600; text-decoration: none; }
        @media (max-width: 480px) {
            .auth-page { padding: 14px 10px; }
            .auth-card { border-radius: 16px; }
            .auth-header { padding: 22px 18px; }
            .auth-body { padding: 22px 18px 24px; }
            .form-control { font-size: 16px !important; }
            .btn-primary { min-height: 44px; padding: 12px; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <div class="auth-header">
            <div class="auth-logo">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="auth-title">Espace Patient</div>
            <div class="auth-subtitle">Accédez à vos rendez-vous et vos réservations</div>
        </div>

        <div class="auth-body">

            <?php if (!empty($flashMessages)): ?>
                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= e($msg['type']) ?>">
                        <div><?= e($msg['text']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div>
                        <?php foreach ($errors as $err): ?>
                            <div><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="patient_email">Adresse email</label>
                    <input
                        type="email"
                        id="patient_email"
                        name="email"
                        class="form-control"
                        placeholder="votre@email.com"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="patient_password">Mot de passe</label>
                    <input
                        type="password"
                        id="patient_password"
                        name="mot_de_passe"
                        class="form-control"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn-primary btn-block" style="margin-top: 10px;">
                    Se connecter
                </button>
            </form>

            <div class="auth-links">
                <p>Nouveau patient ? <a href="<?= BASE_URL ?>/patients/inscription.php">Créer un compte</a></p>
                <p style="margin-top: 6px;"><a href="<?= BASE_URL ?>/index.php">← Retour à l'accueil</a></p>
            </div>

            <div class="switch-box">
                Vous êtes un membre du personnel ?<br>
                <a href="<?= BASE_URL ?>/auth/login.php">Connexion Staff / Espace Pro →</a>
            </div>

        </div>
    </div>
</div>

</body>
</html>
