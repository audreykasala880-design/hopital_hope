<?php
/**
 * ====================================================================
 * DÉCONNEXION SÉCURISÉE
 * ====================================================================
 */

require_once __DIR__ . '/../config/database.php';

// Vider les variables de session
$_SESSION = [];

// Détruire le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Démarrer une nouvelle session éphémère pour le message flash
session_start();
flashMessage('info', "Vous avez été déconnecté avec succès.");

header('Location: ' . BASE_URL . '/auth/login.php');
exit;
