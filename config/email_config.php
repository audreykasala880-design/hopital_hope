<?php
/**
 * ====================================================================
 * CONFIGURATION DE L'ENVOI D'EMAILS — Centre Médical Hope
 * ====================================================================
 * Par défaut, XAMPP fonctionne en "MODE DÉMONSTRATION LOCALE" :
 * Les emails sont tous générés au format HTML et archivés dans le dossier
 * /emails_envoyes/ (consultables directement dans le navigateur).
 * 
 * Si vous souhaitez recevoir les emails RÉELLEMENT dans votre vraie boîte Gmail :
 * 1. Activez la validation en 2 étapes sur votre compte Google.
 * 2. Générez un "Mot de passe d'application" (16 lettres) sur https://myaccount.google.com/apppasswords
 * 3. Renseignez vos identifiants ci-dessous et passez SMTP_ACTIF à true.
 */

define('SMTP_ACTIF', true); // Mettre à true pour envoyer de VRAIS emails sur Internet

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 (STARTTLS) ou 465 (SMTPS)
define('SMTP_SECURE', 'tls'); // 'tls' ou 'ssl'
define('SMTP_USER', 'audreykasala880@gmail.com');
define('SMTP_PASS', 'hnwrvecnfrshwlob');
define('SMTP_FROM_EMAIL', 'contact@hopecmedical.cd');
define('SMTP_FROM_NAME', 'Centre Médical Hope');
