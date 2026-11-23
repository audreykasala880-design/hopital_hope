<?php
/**
 * ====================================================================
 * ESPACE PATIENT - Tableau de bord personnel
 * ====================================================================
 * Réservé aux patients connectés.
 * Permet au patient de :
 * - Consulter son profil médical et ses informations
 * - Visualiser la liste de ses rendez-vous (à venir et passés)
 * - Prendre rapidement un nouveau rendez-vous
 * - Annuler un rendez-vous futur en cas d'imprévu
 */

$pageTitle = "Mon Espace Patient";
require_once __DIR__ . '/../config/database.php';


// Exiger une session patient
requirePatientLogin();

$patientId = (int)$_SESSION['patient_id'];

// Récupérer les informations complètes du patient
$stmtPatient = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmtPatient->execute([$patientId]);
$patient = $stmtPatient->fetch();

if (!$patient) {
    // Si le compte a été supprimé
    unset($_SESSION['patient_id']);
    flashMessage('danger', 'Dossier patient introuvable.');
    header('Location: ' . BASE_URL . '/patients/connexion.php');
    exit;
}

// Traitement de l'annulation d'un rendez-vous par le patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'annuler_rdv') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        flashMessage('danger', 'Jeton de sécurité invalide.');
    } else {
        $rdvId = (int)($_POST['rdv_id'] ?? 0);
        
        // Vérifier que ce RDV appartient bien au patient connecté et est à venir
        $stmtChk = $pdo->prepare("SELECT id, statut, date_heure FROM rendez_vous WHERE id = ? AND id_patient = ?");
        $stmtChk->execute([$rdvId, $patientId]);
        $rdvToCancel = $stmtChk->fetch();

        if ($rdvToCancel) {
            if ($rdvToCancel['statut'] === 'annule') {
                flashMessage('info', 'Ce rendez-vous est déjà annulé.');
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE rendez_vous SET statut = 'annule' WHERE id = ?");
                $stmtUpdate->execute([$rdvId]);
                flashMessage('success', "Votre rendez-vous du " . date('d/m/Y à H:i', strtotime($rdvToCancel['date_heure'])) . " a été annulé.");
            }
        } else {
            flashMessage('danger', 'Rendez-vous introuvable ou non autorisé.');
        }
        header('Location: ' . BASE_URL . '/patients/espace.php');
        exit;
    }
}

// Récupérer tous les rendez-vous de ce patient
$sqlRdv = "
    SELECT 
        r.id,
        r.date_heure,
        r.duree_minutes,
        r.date_heure_fin,
        r.motif,
        r.statut,
        r.remarques,
        m.nom AS medecin_nom,
        m.prenom AS medecin_prenom,
        m.specialite AS medecin_specialite,
        s.nom AS salle_nom,
        s.equipements AS salle_equipements
    FROM rendez_vous r
    JOIN medecins m ON r.id_medecin = m.id
    JOIN salles s ON r.id_salle = s.id
    WHERE r.id_patient = ?
    ORDER BY r.date_heure DESC
";
$stmtRdv = $pdo->prepare($sqlRdv);
$stmtRdv->execute([$patientId]);
$rendezVousList = $stmtRdv->fetchAll();

// Statistiques patient
$maintenant = date('Y-m-d H:i:s');
$totalRdv = count($rendezVousList);
$rdvAVenir = 0;
$rdvPasses = 0;
$rdvAnnules = 0;

foreach ($rendezVousList as $r) {
    if ($r['statut'] === 'annule') {
        $rdvAnnules++;
    } elseif ($r['date_heure'] >= $maintenant) {
        $rdvAVenir++;
    } else {
        $rdvPasses++;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1200px;">

    <!-- Bannière de bienvenue patient -->
    <div class="page-top-banner">
        <div class="page-top-banner-left">
            <div class="page-banner-icon" style="background: linear-gradient(135deg, #059669, #047857); color: #fff;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <div class="page-banner-title">
                    Bonjour, <?= e($patient['prenom']) ?> <?= e($patient['nom']) ?> 
                </div>
                <div class="page-banner-sub">
                    Bienvenue sur votre espace patient Centre Médical Hope • Dossier médical #<?= $patient['id'] ?>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #047857); border: none; padding: 10px 20px; font-weight: 600; box-shadow: 0 4px 14px rgba(5,150,105,0.3);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: middle;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Prendre un rendez-vous
            </a>
            <a href="<?= BASE_URL ?>/patients/deconnexion.php" class="btn btn-secondary" style="font-size: 0.85rem;" onclick="return confirm('Confirmer la déconnexion de votre espace patient ?');">
                Déconnexion
            </a>
        </div>
    </div>

    <!-- 3 Cartes Compteurs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 26px;">
        <div class="card" style="padding: 20px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--r-lg); display: flex; align-items: center; gap: 16px;">
            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(5,150,105,0.12); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                📅
            </div>
            <div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #111827; line-height: 1;"><?= $rdvAVenir ?></div>
                <div style="font-size: 0.82rem; color: #6b7280; font-weight: 500; margin-top: 4px;">Rendez-vous à venir</div>
            </div>
        </div>

        <div class="card" style="padding: 20px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--r-lg); display: flex; align-items: center; gap: 16px;">
            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(59,130,246,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                🩺
            </div>
            <div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #111827; line-height: 1;"><?= $rdvPasses ?></div>
                <div style="font-size: 0.82rem; color: #6b7280; font-weight: 500; margin-top: 4px;">Consultations passées</div>
            </div>
        </div>

        <div class="card" style="padding: 20px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--r-lg); display: flex; align-items: center; gap: 16px;">
            <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(16,185,129,0.12); color: #047857; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                📋
            </div>
            <div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #111827; line-height: 1;"><?= $totalRdv ?></div>
                <div style="font-size: 0.82rem; color: #6b7280; font-weight: 500; margin-top: 4px;">Total des réservations</div>
            </div>
        </div>
    </div>

    <!-- Grille 2 colonnes : Liste des RDV + Carte Profil -->
    <div class="responsive-2col-grid">

        <!-- Colonne gauche : Vos rendez-vous -->
        <div>
            <div class="card" style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--r-xl); padding: 24px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border-color);">
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span>🗓️</span> Mes Rendez-vous
                    </h2>
                    <span style="font-size: 0.8rem; color: #6b7280;">Historique complet</span>
                </div>

                <?php if (empty($rendezVousList)): ?>
                    <div style="text-align: center; padding: 48px 20px;">
                        <div style="width: 72px; height: 72px; margin: 0 auto 16px; border-radius: 50%; background: #ecfdf5; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                            🩺
                        </div>
                        <h3 style="font-size: 1.05rem; font-weight: 600; color: #374151; margin-bottom: 6px;">Aucun rendez-vous pour le moment</h3>
                        <p style="font-size: 0.85rem; color: #6b7280; max-width: 380px; margin: 0 auto 20px;">
                            Prenez rendez-vous en ligne avec l'un de nos praticiens de la Centre Médical Hope en quelques clics.
                        </p>
                        <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #047857); border: none; padding: 11px 22px;">
                            Prendre mon 1er rendez-vous
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($rendezVousList as $rdv): 
                            $isFutur = ($rdv['date_heure'] >= $maintenant && $rdv['statut'] !== 'annule');
                            $ts = strtotime($rdv['date_heure']);
                        ?>
                            <div style="padding: 18px; border-radius: 12px; border: 1px solid <?= $isFutur ? '#a7f3d0' : '#e5e7eb' ?>; background: <?= $isFutur ? '#f0fdf4' : '#ffffff' ?>; transition: all .2s;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 700; font-size: 1rem; color: #111827;">
                                                <?= ucfirst(strftime('%A', $ts)) ? date('d/m/Y', $ts) : date('d/m/Y', $ts) ?> à <?= date('H:i', $ts) ?>
                                            </span>
                                            <span style="font-size: 0.75rem; color: #6b7280;">(<?= $rdv['duree_minutes'] ?> min)</span>
                                        </div>
                                        <div style="font-size: 0.88rem; color: #047857; font-weight: 600; margin-top: 2px;">
                                            Dr. <?= e($rdv['medecin_prenom']) ?> <?= e($rdv['medecin_nom']) ?> • <span style="color: #6b7280; font-weight: 500;"><?= e($rdv['medecin_specialite']) ?></span>
                                        </div>
                                    </div>

                                    <div>
                                        <?php if ($rdv['statut'] === 'confirme'): ?>
                                            <span class="badge" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem;">
                                                ✓ Confirmé
                                            </span>
                                        <?php elseif ($rdv['statut'] === 'en_attente'): ?>
                                            <span class="badge" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem;">
                                                ⏳ En attente
                                            </span>
                                        <?php elseif ($rdv['statut'] === 'annule'): ?>
                                            <span class="badge" style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem;">
                                                ✕ Annulé
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem;">
                                                <?= e(ucfirst($rdv['statut'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; font-size: 0.82rem; color: #4b5563; background: rgba(255,255,255,0.7); padding: 10px 12px; border-radius: 8px; margin-bottom: 8px;">
                                    <div>
                                        <strong>Motif :</strong> <?= e($rdv['motif']) ?>
                                    </div>
                                    <div>
                                        <strong>Salle :</strong> <?= e($rdv['salle_nom']) ?>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.06);">
                                    <a href="<?= BASE_URL ?>/rendez_vous/fiche.php?id=<?= $rdv['id'] ?>" target="_blank" class="btn btn-sm btn-outline-teal" style="font-size: 0.78rem; padding: 5px 12px; display: inline-flex; align-items: center; gap: 5px; border: 1.5px solid #059669; color: #065f46; background: #fff; border-radius: 6px; text-decoration: none; font-weight: 600;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                        Imprimer ma fiche
                                    </a>

                                    <?php if ($isFutur): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="action" value="annuler_rdv">
                                            <input type="hidden" name="rdv_id" value="<?= $rdv['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size: 0.78rem; padding: 5px 12px; color: #dc2626; border: 1px solid #fca5a5; background: #fff; border-radius: 6px; cursor: pointer;">
                                                Annuler ce RDV
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne droite : Profil du Patient -->
        <div>
            <div class="card" style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--r-xl); padding: 24px; box-shadow: var(--shadow-sm); position: sticky; top: 90px;">
                <div style="text-align: center; margin-bottom: 20px; padding-bottom: 18px; border-bottom: 1px solid var(--border-color);">
                    <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #059669, #047857); color: #fff; font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; box-shadow: 0 4px 12px rgba(5,150,105,0.25);">
                        <?= strtoupper(substr($patient['prenom'] ?? 'P', 0, 1)) . strtoupper(substr($patient['nom'] ?? '', 0, 1)) ?>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin: 0;">
                        <?= e($patient['prenom']) ?> <?= e($patient['nom']) ?>
                    </h3>
                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">
                        Patient UCC #<?= $patient['id'] ?>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.85rem;">
                    <div>
                        <div style="color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Email</div>
                        <div style="color: #111827; font-weight: 500; word-break: break-all;"><?= e($patient['email']) ?></div>
                    </div>

                    <div>
                        <div style="color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Téléphone</div>
                        <div style="color: #111827; font-weight: 500;"><?= e($patient['telephone']) ?></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <div>
                            <div style="color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Sexe</div>
                            <div style="color: #111827; font-weight: 500;"><?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></div>
                        </div>
                        <div>
                            <div style="color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Groupe</div>
                            <div style="color: #047857; font-weight: 700;">
                                <?= e($patient['groupe_sanguin'] ?: 'Non renseigné') ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div style="color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Date de naissance</div>
                        <div style="color: #111827; font-weight: 500;"><?= date('d/m/Y', strtotime($patient['date_naissance'])) ?></div>
                    </div>

                    <?php if (!empty($patient['adresse'])): ?>
                        <div>
                            <div style="color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Adresse</div>
                            <div style="color: #111827; font-weight: 500;"><?= e($patient['adresse']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="btn btn-primary" style="width: 100%; text-align: center; display: block; box-sizing: border-box; background: linear-gradient(135deg, #059669, #047857); border: none; padding: 11px; font-weight: 600;">
                        + Nouveau Rendez-vous
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
