<?php
/**
 * EN-TÊTE DU SITE — Design Centre Médical Hope / Centre Médical Hope
 * Style inspiré du dashboard médical moderne (WhatsApp mockups)
 */

if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config/database.php';
}

$pageTitle = $pageTitle ?? "Centre Médical Hope";
$flashMessages = getFlashMessages();

// Détecter la page courante pour le menu actif
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));

// Nom d'affichage de la section courante
$sectionName = "Accueil";
if ($currentScript === 'prendre.php') {
    $sectionName = "Prise de rendez-vous";
} elseif ($currentScript === 'espace.php') {
    $sectionName = "Mon Espace Patient";
} elseif ($currentScript === 'connexion.php') {
    $sectionName = "Connexion Patient";
} elseif ($currentScript === 'inscription.php') {
    $sectionName = "Inscription Patient";
} elseif ($currentDir === 'rendez_vous' && $currentScript === 'index.php') {
    $sectionName = "Liste des rendez-vous";
} elseif ($currentDir === 'rendez_vous' && $currentScript === 'modifier.php') {
    $sectionName = "Modifier le rendez-vous";
} elseif ($currentDir === 'medecins') {
    $sectionName = "Médecins";
} elseif ($currentDir === 'patients') {
    $sectionName = "Gestion Patients";
} elseif ($currentDir === 'salles') {
    $sectionName = "Salles de consultation";
} elseif ($currentDir === 'disponibilites') {
    $sectionName = "Disponibilités";
} elseif ($currentDir === 'statistiques') {
    $sectionName = "Statistiques";
} elseif ($currentDir === 'tests') {
    $sectionName = "Validation Tests TP";
}

$isStaff = isLoggedIn();
$isPatient = isPatientLoggedIn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#082c1e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= e($pageTitle) ?> — Centre Médical Hope</title>
    <meta name="description" content="Application web de gestion des rendez-vous médicaux — Centre Médical Hope Kinshasa">

    <!-- Fonts : Inter & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Principal avec vidage automatique du cache navigateur -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">

    <!-- Favicon SVG en Vert Médical -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%230f764a'/><path d='M50 20 C35 10 20 25 20 40 C20 60 50 82 50 82 C50 82 80 60 80 40 C80 25 65 10 50 20 Z' fill='none' stroke='white' stroke-width='6'/><path d='M35 48 h8 l5 -12 l6 22 l5 -14 l4 4 h8' fill='none' stroke='%2334d399' stroke-width='5' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="app-body">

<div class="app-layout">

    <!-- ============================================================
       SIDEBAR GAUCHE (Navigation Dashboard)
       ============================================================ -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-brand">
                <div class="brand-symbol">
                    <svg viewBox="0 0 40 40" width="32" height="32" fill="none">
                        <rect width="40" height="40" rx="10" fill="#095c3b"/>
                        <path d="M20 9 C13 3 5 13 5 21 C5 29 20 37 20 37 C20 37 35 29 35 21 C35 13 27 3 20 9 Z" stroke="#34d399" stroke-width="2.5" fill="none"/>
                        <path d="M12 21 h4 l3 -6 l4 12 l3 -8 l3 2 h4" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="brand-info">
                    <span class="brand-name">Centre Médical Hope</span>
                    <span class="brand-tag">Centre Médical Hope</span>
                </div>
            </a>
            <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Fermer le menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul class="sidebar-menu">
                <!-- Accueil (visible par tous) -->
                <li>
                    <a href="<?= BASE_URL ?>/index.php" class="sidebar-link <?= $currentScript === 'index.php' && $currentDir !== 'rendez_vous' && $currentDir !== 'patients' ? 'active' : '' ?>">
                        <span class="sidebar-icon">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        </span>
                        <span class="sidebar-text">Accueil</span>
                    </a>
                </li>

                <!-- Prendre RDV (visible par tous, redirige vers connexion si pas connecté) -->
                <li>
                    <a href="<?= BASE_URL ?>/rendez_vous/prendre.php" class="sidebar-link <?= $currentScript === 'prendre.php' ? 'active' : '' ?>">
                        <span class="sidebar-icon">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/><line x1="10" y1="16" x2="14" y2="16"/></svg>
                        </span>
                        <span class="sidebar-text">Prendre RDV</span>
                    </a>
                </li>

                <!-- ESPACE PATIENT -->
                <?php if ($isPatient): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/patients/espace.php" class="sidebar-link <?= $currentScript === 'espace.php' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <span class="sidebar-text">Mon Espace Patient</span>
                        </a>
                    </li>
                <?php elseif (!$isStaff): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/patients/connexion.php" class="sidebar-link <?= $currentScript === 'connexion.php' || $currentScript === 'inscription.php' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            </span>
                            <span class="sidebar-text">Espace Patient</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- SECTION GESTION CLINIQUE (Réservée au Staff : Admin / Réceptionniste) -->
                <?php if ($isStaff): ?>
                    <li style="padding: 12px 16px 4px; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.4);">
                        Gestion Médicale
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/medecins/index.php" class="sidebar-link <?= $currentDir === 'medecins' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <span class="sidebar-text">Médecins</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/patients/index.php" class="sidebar-link <?= $currentDir === 'patients' && $currentScript !== 'espace.php' && $currentScript !== 'connexion.php' && $currentScript !== 'inscription.php' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <span class="sidebar-text">Patients (Base)</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/salles/index.php" class="sidebar-link <?= $currentDir === 'salles' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><circle cx="6" cy="12" r="1"/></svg>
                            </span>
                            <span class="sidebar-text">Salles</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/disponibilites/index.php" class="sidebar-link <?= $currentDir === 'disponibilites' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                            <span class="sidebar-text">Disponibilités</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/rendez_vous/index.php" class="sidebar-link <?= $currentDir === 'rendez_vous' && $currentScript !== 'prendre.php' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </span>
                            <span class="sidebar-text">Liste des RDV</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/statistiques/index.php" class="sidebar-link <?= $currentDir === 'statistiques' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            </span>
                            <span class="sidebar-text">Statistiques</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/tests/run_tests.php" class="sidebar-link <?= $currentDir === 'tests' ? 'active' : '' ?>">
                            <span class="sidebar-icon">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                            </span>
                            <span class="sidebar-text">Tests TP (PO II)</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Bas de sidebar : Profil Utilisateur (Staff ou Patient ou Bouton Connexion) -->
        <div class="sidebar-footer">
            <?php if ($isStaff): ?>
                <div class="sidebar-user">
                    <div class="user-avatar-circle">
                        <span><?= strtoupper(substr($_SESSION['utilisateur_nom'] ?? 'U', 0, 1)) ?></span>
                    </div>
                    <div class="user-details">
                        <strong class="user-name"><?= e($_SESSION['utilisateur_nom']) ?></strong>
                        <span class="user-role"><?= ucfirst(e($_SESSION['utilisateur_role'])) ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-icon-btn" title="Se déconnecter de l'espace pro">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </a>
                </div>
            <?php elseif ($isPatient): ?>
                <div class="sidebar-user">
                    <div class="user-avatar-circle" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <span><?= strtoupper(substr($_SESSION['patient_nom'] ?? 'P', 0, 1)) ?></span>
                    </div>
                    <div class="user-details">
                        <strong class="user-name"><?= e($_SESSION['patient_prenom']) ?> <?= e($_SESSION['patient_nom']) ?></strong>
                        <span class="user-role" style="color: #6ee7b7;">Patient</span>
                    </div>
                    <a href="<?= BASE_URL ?>/patients/deconnexion.php" class="logout-icon-btn" title="Se déconnecter" onclick="return confirm('Confirmer la déconnexion ?');">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="<?= BASE_URL ?>/patients/connexion.php" class="sidebar-login-btn" style="background: rgba(16,185,129,0.18); border-color: rgba(16,185,129,0.4); color: #fff;">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>Espace Patient</span>
                    </a>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="sidebar-login-btn" style="opacity: 0.8; font-size: 0.8rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Espace Pro (Staff)</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Overlay sombre pour mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- ============================================================
       ZONE PRINCIPALE DU DASHBOARD (En-tête haut + Contenu)
       ============================================================ -->
    <div class="app-main">

        <!-- Top bar supérieure -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="topbar-menu-toggle" id="topbarMenuToggle" aria-label="Ouvrir le menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="topbar-page-title"><?= e($sectionName) ?></h1>
            </div>

            <div class="topbar-right">
                <!-- Notifications cliniques -->
                <div class="topbar-action-btn" title="Centre Médical Hope">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="notification-dot"></span>
                </div>

                <!-- Utilisateur dropdown / pill -->
                <?php if ($isStaff): ?>
                    <div class="topbar-user-pill">
                        <div class="topbar-user-avatar">
                            <span><?= strtoupper(substr($_SESSION['utilisateur_nom'] ?? 'A', 0, 1)) ?></span>
                        </div>
                        <span class="topbar-user-name"><?= e($_SESSION['utilisateur_nom']) ?> (<?= ucfirst(e($_SESSION['utilisateur_role'])) ?>)</span>
                    </div>
                <?php elseif ($isPatient): ?>
                    <a href="<?= BASE_URL ?>/patients/espace.php" class="topbar-user-pill" style="text-decoration: none; cursor: pointer;">
                        <div class="topbar-user-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <span><?= strtoupper(substr($_SESSION['patient_nom'] ?? 'P', 0, 1)) ?></span>
                        </div>
                        <span class="topbar-user-name"><?= e($_SESSION['patient_prenom']) ?> <?= e($_SESSION['patient_nom']) ?></span>
                    </a>
                <?php else: ?>
                    <div class="topbar-auth-group">
                        <a href="<?= BASE_URL ?>/patients/connexion.php" class="btn btn-primary btn-sm topbar-btn-patient">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="topbar-btn-label">Espace Patient</span>
                        </a>
                        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary btn-sm topbar-btn-pro">
                            <span class="topbar-btn-label">Espace Pro</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Messages Flash (Succès / Erreurs) -->
        <?php if (!empty($flashMessages)): ?>
            <div class="app-flash-area">
                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= e($msg['type']) ?>">
                        <div class="alert-icon">
                            <?php if ($msg['type'] === 'success'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php elseif ($msg['type'] === 'danger'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="alert-content"><?= e($msg['text']) ?></div>
                        <button class="alert-close" onclick="this.closest('.alert').remove()" aria-label="Fermer">✕</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Contenu injecté des pages -->
        <main class="app-content">
