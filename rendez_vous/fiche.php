<?php
/**
 * ====================================================================
 * FICHE IMPRIMABLE DE RENDEZ-VOUS MÉDICAL — Billet de Consultation
 * ====================================================================
 * Centre Médical Hope — Faculté des Sciences Informatiques
 * 
 * Cette page génère la fiche officielle imprimable du rendez-vous, avec :
 * - En-tête officiel de la Centre Médical Hope & Centre Médical Hope
 * - Référence unique et code-barres / QR Code de contrôle
 * - Informations complètes du patient et du médecin traitant
 * - Détails du créneau horaire, durée et salle d'examen
 * - Consignes cliniques d'arrivée
 * - Style optimisé pour l'impression A4 (@media print)
 */

$pageTitle = "Fiche de Rendez-vous Médical";
require_once __DIR__ . '/../config/database.php';

// Vérification des droits : accessible au patient titulaire OU au personnel staff
if (!isLoggedIn() && !isPatientLoggedIn()) {
    flashMessage('warning', "Veuillez vous connecter pour accéder à votre fiche de rendez-vous.");
    header('Location: ' . BASE_URL . '/patients/connexion.php');
    exit;
}

$rdvId = (int)($_GET['id'] ?? 0);
if ($rdvId <= 0) {
    flashMessage('danger', "Identifiant de rendez-vous invalide.");
    header('Location: ' . (isPatientLoggedIn() ? BASE_URL . '/patients/espace.php' : BASE_URL . '/rendez_vous/index.php'));
    exit;
}

// Récupérer toutes les informations du rendez-vous
$sql = "
    SELECT 
        r.*,
        p.id AS patient_id,
        p.nom AS patient_nom,
        p.prenom AS patient_prenom,
        p.date_naissance AS patient_naissance,
        p.sexe AS patient_sexe,
        p.telephone AS patient_telephone,
        p.email AS patient_email,
        p.adresse AS patient_adresse,
        p.groupe_sanguin AS patient_groupe_sanguin,
        m.nom AS medecin_nom,
        m.prenom AS medecin_prenom,
        m.specialite AS medecin_specialite,
        m.telephone AS medecin_telephone,
        m.email AS medecin_email,
        s.nom AS salle_nom,
        s.etage AS salle_etage,
        s.capacite AS salle_capacite,
        s.equipements AS salle_equipements
    FROM rendez_vous r
    JOIN patients p ON r.id_patient = p.id
    JOIN medecins m ON r.id_medecin = m.id
    JOIN salles s ON r.id_salle = s.id
    WHERE r.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$rdvId]);
$rdv = $stmt->fetch();

if (!$rdv) {
    flashMessage('danger', "Ce rendez-vous n'existe pas ou a été supprimé.");
    header('Location: ' . (isPatientLoggedIn() ? BASE_URL . '/patients/espace.php' : BASE_URL . '/rendez_vous/index.php'));
    exit;
}

// Si l'utilisateur est un patient (non staff), il ne peut consulter QUE sa propre fiche
if (isPatientLoggedIn() && !isLoggedIn()) {
    if ((int)$rdv['id_patient'] !== (int)$_SESSION['patient_id']) {
        flashMessage('danger', "Accès refusé : vous n'êtes pas autorisé à consulter la fiche d'un autre patient.");
        header('Location: ' . BASE_URL . '/patients/espace.php');
        exit;
    }
}

// Formatage des dates
$tsDebut = strtotime($rdv['date_heure']);
$tsFin   = strtotime($rdv['date_heure_fin']);

$joursFr = [
    'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
];
$moisFr = [
    'January' => 'janvier', 'February' => 'février', 'March' => 'mars',
    'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
    'July' => 'juillet', 'August' => 'août', 'September' => 'septembre',
    'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'
];

$nomJour    = $joursFr[date('l', $tsDebut)] ?? date('l', $tsDebut);
$numJour    = date('d', $tsDebut);
$nomMois    = $moisFr[date('F', $tsDebut)] ?? date('F', $tsDebut);
$annee      = date('Y', $tsDebut);
$heureDebut = date('H:i', $tsDebut);
$heureFin   = date('H:i', $tsFin);

$dateAffichee = "$nomJour $numJour $nomMois $annee";

$referenceRdv = "RDV-" . date('Y', $tsDebut) . "-" . str_pad($rdv['id'], 5, '0', STR_PAD_LEFT);

$isNouveau = isset($_GET['success']) && $_GET['success'] == 1;

// Rechercher l'email HTML généré pour ce rendez-vous
$emailFiles = glob(__DIR__ . '/../emails_envoyes/email_rdv_' . $rdv['id'] . '_*.html');
$dernierEmail = !empty($emailFiles) ? basename(end($emailFiles)) : null;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 900px; padding-bottom: 50px;">

    <!-- Zone supérieure d'actions (masquée à l'impression) -->
    <div class="no-print" style="margin-bottom: 24px;">
        
        <?php if ($isNouveau): ?>
            <!-- Alerte de succès après réservation -->
            <div class="alert alert-success" style="background: #ecfdf5; border: 1.5px solid #10b981; border-radius: 14px; padding: 18px 22px; margin-bottom: 20px; box-shadow: 0 4px 14px rgba(16,185,129,0.15);">
                <div style="font-size: 1.4rem; line-height: 1; margin-right: 12px;">🎉</div>
                <div style="flex: 1;">
                    <div style="font-weight: 800; font-size: 1.05rem; color: #065f46; margin-bottom: 4px;">
                        Votre rendez-vous a été confirmé avec succès !
                    </div>
                    <div style="font-size: 0.88rem; color: #047857; line-height: 1.5;">
                        Un <strong>email de confirmation</strong> contenant tous les détails a été envoyé à <strong><?= e($rdv['patient_email']) ?></strong>.
                        Vous pouvez dès à présent <strong>imprimer votre fiche de rendez-vous</strong> ci-dessous ou la sauvegarder en PDF.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; background: #fff; border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 22px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.2rem;">📄</span>
                <div>
                    <strong style="color: #111827; font-size: 0.95rem;">Billet de Consultation Officiel</strong>
                    <div style="font-size: 0.8rem; color: #6b7280;">Référence <?= e($referenceRdv) ?></div>
                </div>
            </div>

            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <?php if ($dernierEmail): ?>
                    <a href="<?= BASE_URL ?>/emails_envoyes/<?= urlencode($dernierEmail) ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.85rem; padding: 9px 14px; border: 1.5px solid #10b981; color: #065f46; background: #ecfdf5; font-weight: 600;" title="Consulter la copie HTML exacte de l'email généré">
                        ✉️ Voir l'email envoyé
                    </a>
                <?php endif; ?>

                <?php if (isPatientLoggedIn() && !isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/patients/espace.php" class="btn btn-secondary" style="font-size: 0.85rem; padding: 9px 16px;">
                        ← Mon espace
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="btn btn-secondary" style="font-size: 0.85rem; padding: 9px 16px;">
                        ← Liste RDV
                    </a>
                <?php endif; ?>

                <button type="button" onclick="window.print();" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #047857); border: none; padding: 9px 20px; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5,150,105,0.3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    Imprimer la fiche (PDF)
                </button>
            </div>
        </div>

    </div>

    <!-- ============================================================
       DOCUMENT MÉDICAL IMPRIMABLE (Format A4 propre)
       ============================================================ -->
    <div class="printable-fiche" id="printableFiche">

        <!-- En-tête Officiel Centre Médical Hope -->
        <div class="fiche-header">
            <div class="fiche-header-left">
                <div class="fiche-logo-box">
                    <svg viewBox="0 0 44 44" width="44" height="44" fill="none">
                        <rect width="44" height="44" rx="10" fill="#064e3b"/>
                        <path d="M22 10 C15 4 6 14 6 23 C6 31 22 40 22 40 C22 40 38 31 38 23 C38 14 29 4 22 10 Z" stroke="#34d399" stroke-width="2.5" fill="none"/>
                        <path d="M13 23 h4 l3 -7 l4 14 l3 -9 l3 2 h5" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="fiche-clinic-title">Centre Médical Hope</h1>
                    <div class="fiche-clinic-sub">Université Catholique du Congo &bull; Centre Médical Hope Kinshasa</div>
                    <div class="fiche-clinic-contact">Kinshasa &bull; Tél: +243 81 000 0000 &bull; contact@hopecmedical.cd</div>
                </div>
            </div>

            <div class="fiche-header-right">
                <div class="fiche-stamp-badge">
                    <span class="stamp-icon">✓</span>
                    <span class="stamp-text">CONFIRMÉ</span>
                </div>
                <div class="fiche-ref-box">
                    <span class="ref-label">RÉFÉRENCE BILLET</span>
                    <span class="ref-val"><?= e($referenceRdv) ?></span>
                </div>
            </div>
        </div>

        <div class="fiche-doc-title-bar">
            <h2>FICHE DE CONSULTATION & BILLET DE RENDEZ-VOUS</h2>
            <div class="fiche-doc-date">Émis le <?= date('d/m/Y à H:i') ?></div>
        </div>

        <!-- Corps du document : 2 Colonnes d'informations -->
        <div class="fiche-grid-info">
            
            <!-- BLOC 1 : INFORMATIONS PATIENT -->
            <div class="fiche-card-section">
                <div class="fiche-card-heading">
                    <span>👤</span> IDENTIFICATION DU PATIENT
                </div>
                <div class="fiche-data-table">
                    <div class="fiche-data-row">
                        <span class="lbl">Nom complet :</span>
                        <span class="val highlight"><?= e($rdv['patient_nom']) ?> <?= e($rdv['patient_prenom']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Dossier médical :</span>
                        <span class="val">#<?= e($rdv['patient_id']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Date de naissance :</span>
                        <span class="val"><?= date('d/m/Y', strtotime($rdv['patient_naissance'])) ?> (<?= $rdv['patient_sexe'] === 'M' ? 'Masculin' : 'Féminin' ?>)</span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Téléphone :</span>
                        <span class="val"><?= e($rdv['patient_telephone']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Email :</span>
                        <span class="val"><?= e($rdv['patient_email']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Groupe sanguin :</span>
                        <span class="val badge-blood"><?= e($rdv['patient_groupe_sanguin'] ?: 'Non renseigné') ?></span>
                    </div>
                </div>
            </div>

            <!-- BLOC 2 : PRATICIEN ET SALLE -->
            <div class="fiche-card-section">
                <div class="fiche-card-heading">
                    <span>🩺</span> CONSULTATION & PRATICIEN
                </div>
                <div class="fiche-data-table">
                    <div class="fiche-data-row">
                        <span class="lbl">Médecin traitant :</span>
                        <span class="val highlight">Dr. <?= e($rdv['medecin_prenom']) ?> <?= e($rdv['medecin_nom']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Spécialité :</span>
                        <span class="val"><?= e($rdv['medecin_specialite']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Lieu / Salle :</span>
                        <span class="val"><?= e($rdv['salle_nom']) ?> (<?= e($rdv['salle_etage']) ?>)</span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Équipements salle :</span>
                        <span class="val" style="font-size: 0.8rem;"><?= e($rdv['salle_equipements']) ?></span>
                    </div>
                    <div class="fiche-data-row">
                        <span class="lbl">Contact médecin :</span>
                        <span class="val"><?= e($rdv['medecin_email']) ?></span>
                    </div>
                </div>
            </div>

        </div>

        <!-- BLOC HORAIRE MAJEUR (Grand bandeau vert de rappel) -->
        <div class="fiche-horaire-banner">
            
                <span class="horaire-label">DATE DE LA CONSULTATION</span>
                <span class="horaire-val"><?= e($dateAffichee) ?></span>
            
            <div class="horaire-divider"></div>
            
                <span class="horaire-label">CRÉNEAU HORAIRE</span>
                <span class="horaire-val"><?= e($heureDebut) ?> — <?= e($heureFin) ?></span>
            
            <div class="horaire-divider"></div>
            
                <span class="horaire-label">DURÉE ESTIMÉE</span>
                <span class="horaire-val"><?= (int)$rdv['duree_minutes'] ?> minutes</span>
            
        </div>

        <!-- BLOC MOTIF & REMARQUES -->
        <div class="fiche-motif-box">
            <div style="margin-bottom: 8px;">
                <strong style="color: #064e3b; font-size: 0.85rem; text-transform: uppercase;">Motif de la consultation :</strong>
                <span style="font-size: 0.95rem; font-weight: 600; color: #111827; margin-left: 6px;"><?= e($rdv['motif']) ?></span>
            </div>
            <?php if (!empty($rdv['equipement_requis'])): ?>
                <div style="font-size: 0.82rem; color: #374151; margin-bottom: 4px;">
                    <strong>Équipement requis spécifique :</strong> <?= e($rdv['equipement_requis']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($rdv['remarques'])): ?>
                <div style="font-size: 0.82rem; color: #4b5563; font-style: italic;">
                    <strong>Remarques / Observations :</strong> <?= e($rdv['remarques']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- BLOC BAS : QR CODE & CONSIGNES OFFICIELLES -->
        <div class="fiche-footer-grid">
            
            <div class="fiche-qr-col">
                <!-- QR Code vectoriel avec informations du rendez-vous -->
                <div class="qr-svg-wrapper">
                    <svg viewBox="0 0 110 110" width="100" height="100" class="qr-svg">
                        <rect width="110" height="110" fill="#ffffff" stroke="#e5e7eb" stroke-width="1"/>
                        <!-- Coins répères standard QR Code -->
                        <!-- Coin Sup Gauche -->
                        <rect x="10" y="10" width="28" height="28" fill="#064e3b" rx="4"/>
                        <rect x="15" y="15" width="18" height="18" fill="#ffffff" rx="2"/>
                        <rect x="19" y="19" width="10" height="10" fill="#064e3b" rx="1"/>
                        <!-- Coin Sup Droit -->
                        <rect x="72" y="10" width="28" height="28" fill="#064e3b" rx="4"/>
                        <rect x="77" y="15" width="18" height="18" fill="#ffffff" rx="2"/>
                        <rect x="81" y="19" width="10" height="10" fill="#064e3b" rx="1"/>
                        <!-- Coin Inf Gauche -->
                        <rect x="10" y="72" width="28" height="28" fill="#064e3b" rx="4"/>
                        <rect x="15" y="77" width="18" height="18" fill="#ffffff" rx="2"/>
                        <rect x="19" y="81" width="10" height="10" fill="#064e3b" rx="1"/>
                        <!-- Motifs de données matricielles simulées -->
                        <rect x="44" y="12" width="6" height="6" fill="#064e3b"/>
                        <rect x="54" y="12" width="6" height="6" fill="#064e3b"/>
                        <rect x="44" y="24" width="6" height="6" fill="#064e3b"/>
                        <rect x="60" y="24" width="6" height="6" fill="#064e3b"/>
                        <rect x="12" y="44" width="6" height="6" fill="#064e3b"/>
                        <rect x="24" y="44" width="6" height="6" fill="#064e3b"/>
                        <rect x="36" y="44" width="6" height="6" fill="#064e3b"/>
                        <rect x="48" y="44" width="14" height="6" fill="#064e3b"/>
                        <rect x="70" y="44" width="6" height="6" fill="#064e3b"/>
                        <rect x="82" y="44" width="16" height="6" fill="#064e3b"/>
                        <rect x="44" y="56" width="6" height="12" fill="#064e3b"/>
                        <rect x="56" y="56" width="12" height="6" fill="#064e3b"/>
                        <rect x="76" y="56" width="6" height="12" fill="#064e3b"/>
                        <rect x="90" y="56" width="8" height="6" fill="#064e3b"/>
                        <rect x="44" y="74" width="6" height="6" fill="#064e3b"/>
                        <rect x="60" y="74" width="8" height="8" fill="#064e3b"/>
                        <rect x="76" y="74" width="6" height="6" fill="#064e3b"/>
                        <rect x="90" y="74" width="8" height="6" fill="#064e3b"/>
                        <rect x="44" y="88" width="12" height="10" fill="#064e3b"/>
                        <rect x="64" y="88" width="6" height="10" fill="#064e3b"/>
                        <rect x="78" y="88" width="12" height="10" fill="#064e3b"/>
                    </svg>
                </div>
                <div class="qr-caption">Scanner à l'accueil pour vérification rapide</div>
            </div>

            <div class="fiche-instructions-col">
                <h4>CONSIGNES IMPORTANTES POUR LE PATIENT</h4>
                <ul>
                    <li>Prière de vous présenter à l'accueil clinique <strong>15 minutes</strong> avant l'heure du rendez-vous.</li>
                    <li>Munissez-vous de cette <strong>fiche imprimée</strong> (ou présentée sur votre smartphone) et d'une <strong>pièce d'identité</strong> valide.</li>
                    <li>Apportez vos ordonnances médicales en cours, radiographies ou récents résultats de laboratoire.</li>
                    <li>En cas d'empêchement, vous pouvez annuler votre rendez-vous depuis votre <a href="<?= BASE_URL ?>/patients/espace.php">espace patient</a> au moins 24 heures à l'avance.</li>
                </ul>
            </div>

        </div>

        <!-- SIGNATURES ET CACHETS OFFICIELS -->
        <div class="fiche-signatures-grid">
            <div class="sig-box">
                <span class="sig-title">Cachet du Service d'Accueil</span>
                <div class="sig-stamp-circle">
                    Centre Médical Hope<br>
                    ★ Centre Médical Hope ★<br>
                    RÉCEPTION
                </div>
            </div>
            <div class="sig-box">
                <span class="sig-title">Visa du Praticien</span>
                <div class="sig-line">Dr. <?= e($rdv['medecin_nom']) ?></div>
            </div>
        </div>

        <!-- Mentions légales de bas de page -->
        <div class="fiche-legal-footer">
            Centre Médical Hope — Faculté des Sciences Informatiques (Centre Médical Hope) &bull; Kinshasa, Kinshasa, RDC &bull; Document officiel généré par le système informatique de gestion des rendez-vous
        </div>

    </div><!-- /.printable-fiche -->

</div>

<style>
/* ============================================================
   STYLES ÉCRAN DE LA FICHE IMPRIMABLE
   ============================================================ */
.printable-fiche {
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 36px 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: #111827;
}

.fiche-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 20px;
    border-bottom: 2px solid #064e3b;
    gap: 16px;
}

.fiche-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.fiche-clinic-title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #064e3b;
    margin: 0 0 2px;
    letter-spacing: 0.5px;
}

.fiche-clinic-sub {
    font-size: 0.85rem;
    font-weight: 600;
    color: #047857;
    margin-bottom: 3px;
}

.fiche-clinic-contact {
    font-size: 0.75rem;
    color: #6b7280;
}

.fiche-header-right {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.fiche-stamp-badge {
    background: #ecfdf5;
    border: 2px solid #059669;
    border-radius: 8px;
    padding: 4px 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #065f46;
    font-weight: 800;
    font-size: 0.8rem;
    letter-spacing: 1px;
}

.fiche-ref-box {
    text-align: right;
}

.ref-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 700;
    color: #6b7280;
    letter-spacing: 1px;
}

.ref-val {
    font-family: 'Courier New', monospace;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.fiche-doc-title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0fdf4;
    border-left: 4px solid #059669;
    padding: 10px 16px;
    margin: 20px 0;
    border-radius: 4px;
}

.fiche-doc-title-bar h2 {
    font-family: 'Poppins', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    color: #065f46;
    margin: 0;
    letter-spacing: 0.5px;
}

.fiche-doc-date {
    font-size: 0.78rem;
    color: #047857;
    font-weight: 500;
}

.fiche-grid-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 22px;
}

.fiche-card-section {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px 18px;
}

.fiche-card-heading {
    font-size: 0.8rem;
    font-weight: 700;
    color: #064e3b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
}

.fiche-data-table {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.fiche-data-row {
    display: flex;
    font-size: 0.825rem;
    line-height: 1.4;
}

.fiche-data-row .lbl {
    width: 130px;
    flex-shrink: 0;
    color: #6b7280;
    font-weight: 500;
}

.fiche-data-row .val {
    color: #111827;
    font-weight: 600;
}

.fiche-data-row .val.highlight {
    color: #064e3b;
    font-weight: 700;
}

.badge-blood {
    background: #fee2e2;
    color: #991b1b;
    padding: 1px 6px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 0.75rem;
}

.fiche-horaire-banner {
    background: linear-gradient(135deg, #064e3b, #059669);
    border-radius: 10px;
    color: #ffffff;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 18px 20px;
    margin-bottom: 20px;
    text-align: center;
}

.horaire-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    color: #a7f3d0;
    letter-spacing: 0.8px;
    margin-bottom: 4px;
}

.horaire-val {
    font-family: 'Poppins', sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
    color: #ffffff;
}

.horaire-divider {
    width: 1px;
    height: 38px;
    background: rgba(255,255,255,0.25);
}

.fiche-motif-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 22px;
}

.fiche-footer-grid {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 20px;
    align-items: center;
    padding: 16px 0;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 24px;
}

.fiche-qr-col {
    text-align: center;
}

.qr-caption {
    font-size: 0.65rem;
    color: #6b7280;
    margin-top: 4px;
    line-height: 1.2;
}

.fiche-instructions-col h4 {
    font-size: 0.8rem;
    font-weight: 700;
    color: #064e3b;
    margin: 0 0 6px;
    letter-spacing: 0.5px;
}

.fiche-instructions-col ul {
    margin: 0;
    padding-left: 18px;
    font-size: 0.78rem;
    color: #374151;
    line-height: 1.5;
}

.fiche-instructions-col li {
    margin-bottom: 3px;
}

.fiche-signatures-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 24px;
}

.sig-box {
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 12px;
    min-height: 90px;
    text-align: center;
    position: relative;
}

.sig-title {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.sig-stamp-circle {
    display: inline-block;
    border: 2px dashed #059669;
    border-radius: 50%;
    width: 64px;
    height: 64px;
    padding: 10px 4px;
    color: #059669;
    font-size: 0.58rem;
    font-weight: 800;
    line-height: 1.1;
    transform: rotate(-10deg);
}

.sig-line {
    margin-top: 36px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    border-top: 1px solid #cbd5e1;
    padding-top: 4px;
}

.fiche-legal-footer {
    text-align: center;
    font-size: 0.68rem;
    color: #9ca3af;
    line-height: 1.4;
}

/* ============================================================
   STYLES IMPRESSION (WINDOW.PRINT / PDF)
   ============================================================ */
@media print {
    /* Masquer la structure de l'application web */
    .app-sidebar,
    .app-topbar,
    .app-footer,
    .sidebar-backdrop,
    .no-print,
    .app-flash-area {
        display: none !important;
    }

    body, .app-body, .app-layout, .app-main, .app-content {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        width: 100% !important;
    }

    .container {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .printable-fiche {
        border: none !important;
        box-shadow: none !important;
        padding: 10mm 12mm !important;
        width: 100% !important;
        page-break-inside: avoid;
    }

    .fiche-horaire-banner {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: #064e3b !important;
        color: #ffffff !important;
    }

    .horaire-val {
        color: #ffffff !important;
    }

    .fiche-stamp-badge {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: #ecfdf5 !important;
        border-color: #059669 !important;
    }

    .fiche-doc-title-bar {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background: #f0fdf4 !important;
    }

    @page {
        size: A4 portrait;
        margin: 8mm;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
