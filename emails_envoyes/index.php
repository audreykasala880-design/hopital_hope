<?php
/**
 * ====================================================================
 * HISTORIQUE ET PRÉVISUALISATION DES EMAILS ENVOYÉS
 * ====================================================================
 * Outil de démonstration et de vérification pour le TP (Environnement local).
 * Permet de visualiser les emails HTML générés et envoyés par le système.
 */

$pageTitle = "Emails de confirmation envoyés";
require_once __DIR__ . '/../config/database.php';

// Accessible au personnel staff ou aux patients connectés
if (!isLoggedIn() && !isPatientLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$dir = __DIR__;
$files = glob($dir . '/*.html');
// Trier par date décroissante
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1000px;">

    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon" style="background: linear-gradient(135deg, #059669, #047857); color: #fff;">
                ✉️
            </div>
            <div>
                <div class="page-banner-title">Journal des Emails de Confirmation</div>
                <div class="page-banner-sub">Historique des notifications de rendez-vous générées par SantéPlus</div>
            </div>
        </div>
        <div>
            <?php if (isPatientLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/patients/espace.php" class="btn btn-secondary">← Mon Espace Patient</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary">← Gestion des Rendez-vous</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--r-xl); padding: 24px; box-shadow: var(--shadow-sm);">
        
        <?php if (empty($files)): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 2.5rem; margin-bottom: 12px;">📭</div>
                <h3 style="color: #374151; font-size: 1.1rem; margin-bottom: 6px;">Aucun email archivé pour l'instant</h3>
                <p style="color: #6b7280; font-size: 0.88rem;">Dès qu'un rendez-vous sera confirmé, l'email envoyé s'affichera ici.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb; text-align: left; font-size: 0.82rem; color: #6b7280;">
                            <th style="padding: 10px 14px;">Date d'envoi</th>
                            <th style="padding: 10px 14px;">Fichier Email HTML</th>
                            <th style="padding: 10px 14px;">Taille</th>
                            <th style="padding: 10px 14px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $f): 
                            $basename = basename($f);
                            $dateModif = date('d/m/Y à H:i:s', filemtime($f));
                            $tailleKo = round(filesize($f) / 1024, 1);
                        ?>
                            <tr style="border-bottom: 1px solid #f3f4f6; font-size: 0.88rem;">
                                <td style="padding: 12px 14px; font-weight: 600; color: #111827;">
                                    <?= $dateModif ?>
                                </td>
                                <td style="padding: 12px 14px; color: #065f46; font-family: monospace;">
                                    <?= e($basename) ?>
                                </td>
                                <td style="padding: 12px 14px; color: #6b7280;">
                                    <?= $tailleKo ?> Ko
                                </td>
                                <td style="padding: 12px 14px; text-align: right;">
                                    <a href="<?= BASE_URL ?>/emails_envoyes/<?= urlencode($basename) ?>" target="_blank" class="btn btn-sm btn-outline-teal" style="font-size: 0.8rem; padding: 5px 12px; border: 1px solid #059669; color: #065f46; border-radius: 6px; text-decoration: none;">
                                         Ouvrir l'email
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
