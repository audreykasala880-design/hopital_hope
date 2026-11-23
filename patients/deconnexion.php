<?php
/**
 * ====================================================================
 * DÉCONNEXION PATIENT
 * ====================================================================
 * Détruit uniquement les données de session du patient, sans impacter
 * l'authentification staff s'il y a lieu.
 */

require_once __DIR__ . '/../config/database.php';

unset($_SESSION['patient_id']);
unset($_SESSION['patient_nom']);
unset($_SESSION['patient_prenom']);
unset($_SESSION['patient_email']);

flashMessage('info', 'Vous avez été déconnecté de votre espace patient.');
header('Location: ' . BASE_URL . '/index.php');
exit;
