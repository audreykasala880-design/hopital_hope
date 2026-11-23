<?php
/**
 * ====================================================================
 * MODULE D'ENVOI D'EMAILS — Centre Médical Hope
 * ====================================================================
 * Gère l'envoi des confirmations de rendez-vous médicaux en HTML responsive.
 * Enregistre également une copie archivée de chaque email dans le dossier
 * /emails_envoyes/ pour consultation en environnement local / démonstration.
 */

if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config/database.php';
}
if (file_exists(__DIR__ . '/../config/email_config.php')) {
    require_once __DIR__ . '/../config/email_config.php';
}
if (file_exists(__DIR__ . '/smtp_client.php')) {
    require_once __DIR__ . '/smtp_client.php';
}

/**
 * Envoie un email de confirmation de rendez-vous au patient et archive une copie HTML.
 * 
 * @param int $rdvId Identifiant unique du rendez-vous
 * @param PDO $pdo Connexion à la base de données
 * @return array ['success' => bool, 'message' => string, 'archive_file' => string|null]
 */
function envoyerEmailConfirmationRdv($rdvId, $pdo) {
    // 1. Récupérer toutes les données liées au rendez-vous
    $sql = "
        SELECT 
            r.id,
            r.date_heure,
            r.duree_minutes,
            r.date_heure_fin,
            r.motif,
            r.remarques,
            r.statut,
            p.id AS patient_id,
            p.nom AS patient_nom,
            p.prenom AS patient_prenom,
            p.email AS patient_email,
            p.telephone AS patient_telephone,
            p.groupe_sanguin,
            m.nom AS medecin_nom,
            m.prenom AS medecin_prenom,
            m.specialite AS medecin_specialite,
            m.email AS medecin_email,
            s.nom AS salle_nom,
            s.etage AS salle_etage,
            s.equipements AS salle_equipements
        FROM rendez_vous r
        JOIN patients p ON r.id_patient = p.id
        JOIN medecins m ON r.id_medecin = m.id
        JOIN salles s ON r.id_salle = s.id
        WHERE r.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$rdvId]);
    $rdv = $stmt->fetch();

    if (!$rdv) {
        return [
            'success' => false,
            'message' => "Rendez-vous #$rdvId introuvable.",
            'archive_file' => null
        ];
    }

    if (empty($rdv['patient_email'])) {
        return [
            'success' => false,
            'message' => "Le patient ne possède pas d'adresse email enregistrée.",
            'archive_file' => null
        ];
    }

    // 2. Formater les dates et heures
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

    $jourSemaine = $joursFr[date('l', $tsDebut)] ?? date('l', $tsDebut);
    $jourMois    = date('d', $tsDebut);
    $mois        = $moisFr[date('F', $tsDebut)] ?? date('F', $tsDebut);
    $annee       = date('Y', $tsDebut);
    $heureDebut  = date('H:i', $tsDebut);
    $heureFin    = date('H:i', $tsFin);

    $dateFormatee = "$jourSemaine $jourMois $mois $annee";

    // URL absolue de la fiche imprimable
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $urlFiche = $scheme . '://' . $host . BASE_URL . '/rendez_vous/fiche.php?id=' . $rdv['id'];

    // 3. Sujet du mail
    $sujet = "Confirmation de votre Rendez-vous Médical #RDV-" . str_pad($rdv['id'], 5, '0', STR_PAD_LEFT) . " — Centre Médical Hope";

    // 4. Corps HTML de l'email (Design Centre Médical Hope vert médical)
    $corpsHtml = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($sujet) . '</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f3f4f6; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased; }
        .email-wrapper { width: 100%; background-color: #f3f4f6; padding: 30px 10px; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .email-header { background: linear-gradient(135deg, #052e16 0%, #064e3b 50%, #065f46 100%); padding: 32px 30px; text-align: center; color: #ffffff; }
        .email-brand-badge { display: inline-block; background: rgba(52, 211, 153, 0.2); border: 1px solid rgba(52, 211, 153, 0.35); padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #a7f3d0; text-transform: uppercase; margin-bottom: 12px; }
        .email-header h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #ffffff; }
        .email-header p { margin: 0; font-size: 13px; color: #d1fae5; }
        .email-body { padding: 32px 30px; color: #1f2937; line-height: 1.6; }
        .patient-greeting { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 14px; }
        .intro-text { font-size: 14px; color: #4b5563; margin-bottom: 24px; }
        .rdv-card { background: #f0fdf4; border: 1.5px solid #a7f3d0; border-radius: 14px; padding: 22px; margin-bottom: 26px; }
        .rdv-card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #d1fae5; padding-bottom: 12px; margin-bottom: 14px; }
        .rdv-ref { font-size: 13px; font-weight: 800; color: #065f46; letter-spacing: 0.5px; }
        .rdv-badge-status { background: #059669; color: #ffffff; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .rdv-detail-row { margin-bottom: 10px; font-size: 14px; display: table; width: 100%; }
        .rdv-detail-label { display: table-cell; width: 140px; color: #047857; font-weight: 600; font-size: 13px; }
        .rdv-detail-value { display: table-cell; color: #111827; font-weight: 700; }
        .btn-print-box { text-align: center; margin: 28px 0; }
        .btn-print { display: inline-block; background: linear-gradient(135deg, #059669, #047857); color: #ffffff !important; text-decoration: none; padding: 13px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(5,150,105,0.35); }
        .instructions-box { background: #f8fafc; border-left: 4px solid #059669; padding: 16px 18px; border-radius: 8px; margin-bottom: 24px; }
        .instructions-title { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .instructions-list { margin: 0; padding-left: 18px; font-size: 12.5px; color: #475569; }
        .instructions-list li { margin-bottom: 4px; }
        .email-footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 22px 30px; text-align: center; font-size: 11.5px; color: #6b7280; line-height: 1.5; }
        .email-footer strong { color: #374151; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            
            <!-- En-tête Médical -->
            <div class="email-header">
                <div class="email-brand-badge">Centre Médical Hope</div>
                <h1>Rendez-vous Confirmé</h1>
                <p>Votre demande de consultation médicale a été validée avec succès</p>
            </div>

            <!-- Corps de l\'email -->
            <div class="email-body">
                <div class="patient-greeting">
                    Bonjour ' . htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']) . ',
                </div>
                <div class="intro-text">
                    Nous avons le plaisir de vous confirmer l\'enregistrement de votre consultation auprès de notre Centre Médical Hope. Vous trouverez ci-dessous le récapitulatif complet de votre réservation :
                </div>

                <!-- Carte détails du RDV -->
                <div class="rdv-card">
                    <div class="rdv-card-header">
                        <span class="rdv-ref">RÉFÉRENCE : RDV-' . str_pad($rdv['id'], 5, '0', STR_PAD_LEFT) . '</span>
                        <span class="rdv-badge-status">CONFIRMÉ</span>
                    </div>

                    <div class="rdv-detail-row">
                        <span class="rdv-detail-label"> Date :</span>
                        <span class="rdv-detail-value">' . htmlspecialchars($dateFormatee) . '</span>
                    </div>
                    <div class="rdv-detail-row">
                        <span class="rdv-detail-label"> Heure :</span>
                        <span class="rdv-detail-value">' . htmlspecialchars($heureDebut) . ' à ' . htmlspecialchars($heureFin) . ' (' . (int)$rdv['duree_minutes'] . ' min)</span>
                    </div>
                    <div class="rdv-detail-row">
                        <span class="rdv-detail-label"> Praticien :</span>
                        <span class="rdv-detail-value">Dr. ' . htmlspecialchars($rdv['medecin_prenom'] . ' ' . $rdv['medecin_nom']) . '</span>
                    </div>
                    <div class="rdv-detail-row">
                        <span class="rdv-detail-label"> Spécialité :</span>
                        <span class="rdv-detail-value">' . htmlspecialchars($rdv['medecin_specialite']) . '</span>
                    </div>
                    <div class="rdv-detail-row">
                        <span class="rdv-detail-label"> Salle :</span>
                        <span class="rdv-detail-value">' . htmlspecialchars($rdv['salle_nom']) . ' (' . htmlspecialchars($rdv['salle_etage']) . ')</span>
                    </div>
                    <div class="rdv-detail-row">
                        <span class="rdv-detail-label"> Motif :</span>
                        <span class="rdv-detail-value">' . htmlspecialchars($rdv['motif']) . '</span>
                    </div>
                </div>

                <!-- Bouton action Imprimer / Fiche -->
                <div class="btn-print-box">
                    <a href="' . htmlspecialchars($urlFiche) . '" class="btn-print" target="_blank">
                         Télécharger & Imprimer ma fiche de rendez-vous
                    </a>
                </div>

                <!-- Consignes pour la consultation -->
                <div class="instructions-box">
                    <div class="instructions-title">ℹ️ Consignes pour votre venue à la clinique :</div>
                    <ul class="instructions-list">
                        <li>Présentez-vous à l\'accueil de la clinique <strong>15 minutes avant</strong> l\'horaire fixé.</li>
                        <li>Munissez-vous de votre <strong>fiche imprimée</strong> (ou affichez-la sur votre téléphone) et d\'une <strong>pièce d\'identité</strong>.</li>
                        <li>Apportez vos ordonnances en cours et éventuels examens radiologiques ou analyses antérieurs.</li>
                        <li>En cas de retard ou d\'empêchement, vous pouvez annuler votre rendez-vous depuis votre <a href="' . htmlspecialchars($scheme . '://' . $host . BASE_URL . '/patients/espace.php') . '" style="color: #059669; font-weight: 600;">espace patient en ligne</a>.</li>
                    </ul>
                </div>

                <p style="font-size: 13px; color: #6b7280; margin: 0;">
                    Pour toute urgence ou question, contactez notre secrétariat au <strong>+243 81 000 0000</strong> (24h/24).
                </p>
            </div>

            <!-- Pied de page -->
            <div class="email-footer">
                <strong>Centre Médical Hope — Faculté des Sciences Informatiques (Centre Médical Hope)</strong><br>
                Kinshasa, Boulevard Lumumba, Kinshasa, RDC<br>
                Ceci est un message automatique de confirmation de votre rendez-vous médical.
            </div>

        </div>
    </div>
</body>
</html>
';

    // 5. Tentative d'envoi d'email
    $mailSent = false;
    $smtpError = null;

    if (defined('SMTP_ACTIF') && SMTP_ACTIF === true && function_exists('envoyerEmailViaSmtp')) {
        // Envoi réel via SMTP distant (Gmail, Brevo, etc.)
        $smtpRes = envoyerEmailViaSmtp($rdv['patient_email'], $sujet, $corpsHtml);
        $mailSent = $smtpRes['success'];
        $smtpError = $smtpRes['error'] ?? null;
    } else {
        // Envoi via fonction mail() native de PHP
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Centre Médical Hope Centre Médical Hope <contact@hopecmedical.cd>\r\n";
        $headers .= "Reply-To: accueil@hopecmedical.cd\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $mailSent = @mail($rdv['patient_email'], $sujet, $corpsHtml, $headers);
    }

    // 7. Enregistrement systématique de l'archive HTML dans /emails_envoyes/
    $archiveDir = __DIR__ . '/../emails_envoyes';
    if (!is_dir($archiveDir)) {
        @mkdir($archiveDir, 0777, true);
    }

    $fileName = 'email_rdv_' . $rdv['id'] . '_' . date('Ymd_His') . '.html';
    $filePath = $archiveDir . '/' . $fileName;

    // Métadonnées d'envoi en haut du fichier HTML pour la prévisualisation
    $metaBanner = '<!-- ARCHIVE EMAIL CONFIRMATION RDV #' . $rdv['id'] . "\n"
                . 'Destinataire : ' . $rdv['patient_email'] . "\n"
                . 'Date d\'envoi  : ' . date('Y-m-d H:i:s') . "\n"
                . 'Statut SMTP   : ' . ($mailSent ? 'ENVOYÉ AVEC SUCCÈS VIA SMTP' : 'NON TRANSMIS VIA SMTP' . ($smtpError ? " ($smtpError)" : '')) . "\n-->\n";

    @file_put_contents($filePath, $metaBanner . $corpsHtml);

    // Mettre à jour un fichier index simple des emails envoyés
    $journalPath = $archiveDir . '/journal_envois.json';
    $journal = [];
    if (file_exists($journalPath)) {
        $journal = json_decode(@file_get_contents($journalPath) ?: '[]', true) ?: [];
    }
    $journal[] = [
        'rdv_id'       => $rdv['id'],
        'patient'      => $rdv['patient_prenom'] . ' ' . $rdv['patient_nom'],
        'email'        => $rdv['patient_email'],
        'sujet'        => $sujet,
        'date_envoi'   => date('Y-m-d H:i:s'),
        'fichier_html' => $fileName,
        'mail_succes'  => $mailSent,
        'smtp_erreur'  => $smtpError
    ];
    @file_put_contents($journalPath, json_encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return [
        'success'      => true,
        'mail_sent'    => $mailSent,
        'destinataire' => $rdv['patient_email'],
        'archive_file' => $fileName,
        'message'      => "Email de confirmation généré pour " . $rdv['patient_email']
    ];
}
