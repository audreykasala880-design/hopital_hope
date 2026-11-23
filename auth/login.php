<?php
/**
 * PAGE DE CONNEXION — Design Premium v2.0
 */

$pageTitle = "Connexion — Espace Professionnel";
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Jeton de sécurité invalide. Veuillez rafraîchir la page.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = "Veuillez saisir votre email et votre mot de passe.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                session_regenerate_id(true);
                $_SESSION['utilisateur_id']    = $user['id'];
                $_SESSION['utilisateur_nom']   = $user['nom'];
                $_SESSION['utilisateur_email'] = $user['email'];
                $_SESSION['utilisateur_role']  = $user['role'];

                flashMessage('success', "Bienvenue, " . $user['nom'] . " ! Vous êtes connecté en tant que " . ucfirst($user['role']) . ".");
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            } else {
                $errors[] = "Identifiants incorrects. Vérifiez votre adresse email et votre mot de passe.";
            }
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
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%230f764a'/><path d='M44 20h12v60H44zM20 44h60v12H20z' fill='white'/></svg>">
    <style>
        /* Styles spécifiques page login — auth-* dupliqués depuis inscription.php */
        .auth-page { min-height: 100vh; background: linear-gradient(135deg, #052e16 0%, #064e3b 40%, #065f46 100%); display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
        .auth-card { background: #fff; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); width: 100%; max-width: 440px; overflow: hidden; }
        .auth-header { background: linear-gradient(135deg, #064e3b, #059669); padding: 28px 36px; text-align: center; }
        .auth-logo { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
        .auth-title { color: #fff; font-family: 'Poppins', sans-serif; font-size: 1.4rem; font-weight: 700; margin: 0 0 4px; }
        .auth-subtitle { color: rgba(255,255,255,0.75); font-size: 0.85rem; margin: 0; }
        .auth-body { padding: 32px 36px 36px; }
        .auth-demo-box { margin-top: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; }
        .auth-demo-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 10px; }
        .auth-demo-btn { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: border-color .15s; width: 100%; text-align: left; margin-bottom: 8px; }
        .auth-demo-btn:last-child { margin-bottom: 0; }
        .auth-demo-btn:hover { border-color: #34d399; background: #f0fdf4; }
        .auth-demo-icon { width: 32px; height: 32px; border-radius: 8px; background: #d1fae5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; }
        .auth-demo-info strong { display: block; font-size: 0.82rem; font-weight: 700; color: #111827; }
        .auth-demo-info span { font-size: 0.75rem; color: #6b7280; }
        @media (max-width: 480px) {
            .auth-page { padding: 14px 10px; }
            .auth-card { border-radius: 16px; }
            .auth-header { padding: 22px 18px; }
            .auth-body { padding: 22px 18px 24px; }
            .form-control { font-size: 16px !important; }
            .auth-demo-btn { padding: 8px 10px; font-size: 0.78rem; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- Header de la carte -->
        <div class="auth-header">
            <div class="auth-logo">
                <svg viewBox="0 0 40 40" width="36" height="36" fill="none">
                    <path d="M17 8h6v24H17z" fill="#fff"/>
                    <path d="M8 17h24v6H8z" fill="#fff"/>
                </svg>
            </div>
            <div class="auth-title">Espace Professionnel</div>
            <div class="auth-subtitle">Centre Médical Hope · Kinshasa</div>
        </div>

        <!-- Corps de la carte -->
        <div class="auth-body">

            <!-- Erreurs -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <div class="alert-icon">✕</div>
                    <div class="alert-content">
                        <?php foreach ($errors as $e_msg): ?>
                            <?= e($e_msg) ?><br>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="login_email">
                        Adresse Email professionnelle
                    </label>
                    <input
                        type="email"
                        id="login_email"
                        name="email"
                        class="form-control"
                        placeholder="exemple@hopecmedical.cd"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="login_password">
                        Mot de passe
                    </label>
                    <input
                        type="password"
                        id="login_password"
                        name="mot_de_passe"
                        class="form-control"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 8px;">
                    Se connecter à l'espace pro
                </button>
            </form>

            <p style="text-align:center; font-size:0.8rem; color:var(--text-muted); margin: 16px 0 0;">
                <a href="<?= BASE_URL ?>/index.php" style="color: var(--text-secondary);">← Retour à l'accueil public</a>
            </p>

            <!-- Comptes de démonstration -->
            <div class="auth-demo-box">
                <div class="auth-demo-title">💡 Comptes de démonstration</div>

                <button type="button" class="auth-demo-btn" onclick="fillLogin('admin@ucc-clinique.cd','admin123')">
                    <div class="auth-demo-icon">👑</div>
                    <div class="auth-demo-info">
                        <strong>Administrateur</strong>
                        <span>admin@ucc-clinique.cd · admin123</span>
                    </div>
                </button>

                <button type="button" class="auth-demo-btn" onclick="fillLogin('reception@ucc-clinique.cd','admin123')">
                    <div class="auth-demo-icon" style="background: var(--grad-teal);">🏥</div>
                    <div class="auth-demo-info">
                        <strong>Réceptionniste</strong>
                        <span>reception@ucc-clinique.cd · admin123</span>
                    </div>
                </button>
            </div>

        </div><!-- /.auth-body -->
    </div><!-- /.auth-card -->
</div><!-- /.auth-page -->

<script>
function fillLogin(email, pass) {
    document.getElementById('login_email').value = email;
    document.getElementById('login_password').value = pass;
    document.getElementById('login_email').dispatchEvent(new Event('input'));
    document.getElementById('loginForm').querySelector('[type="submit"]').focus();
}
</script>
</body>
</html>
