<?php
/**
 * ====================================================================
 * CONFIGURATION DE LA BASE DE DONNÉES ET FONCTIONS UTILES (HELPERS)
 * ====================================================================
 * Projet : Gestion des Rendez-vous Médicaux (Centre Médical Hope)
 * Cours  : Programmation Web II - 2e Licence Sciences Informatiques
 * Université Catholique du Congo (UCC)
 * 
 * Ce fichier initialise la connexion PDO avec MySQL et fournit les
 * fonctions réutilisables pour la sécurité, les sessions et les règles
 * de gestion des réservations.
 */

// Démarrer la session PHP si elle n'est pas encore active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------
// 1. PARAMÈTRES DE CONNEXION MYSQL (XAMPP PAR DÉFAUT)
// --------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'gestion_rendez_vous_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Par défaut dans XAMPP, le mot de passe root est vide

// Détection dynamique de l'URL racine du projet
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
// Si on est dans un sous-dossier (ex: /gestion-rendez-vous-medicaux/patients), on remonte à la racine
$pos = strpos($scriptDir, 'gestion-rendez-vous-medicaux');
if ($pos !== false) {
    define('BASE_URL', substr($scriptDir, 0, $pos + strlen('gestion-rendez-vous-medicaux')));
} else {
    define('BASE_URL', '');
}

/**
 * Fonction de connexion à la base de données via PDO
 * @return PDO Instance de connexion PDO prête à l'emploi
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lancer des exceptions en cas d'erreur
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourner des tableaux associatifs
                PDO::ATTR_EMULATE_PREPARES   => false,                  // Vraies requêtes préparées MySQL
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("<div style='font-family: Arial; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px;'>
                    <h2>Erreur de connexion à la base de données</h2>
                    <p>Vérifiez que MySQL est bien démarré dans le panneau de contrôle XAMPP.</p>
                    <p><strong>Détail technique :</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                 </div>");
        }
    }
    return $pdo;
}

// Instance globale $pdo pour simplifier les scripts de TP
$pdo = getDBConnection();

// --------------------------------------------------------------------
// 2. FONCTIONS DE SÉCURITÉ
// --------------------------------------------------------------------

/**
 * Échappement des chaînes de caractères pour prévenir les attaques XSS
 * @param string|null $string Donnée brute venant de la BDD ou du formulaire
 * @return string Donnée sécurisée pour l'affichage HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un jeton CSRF et le stocke en session pour protéger les formulaires
 * @return string Jeton unique aléatoire
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité du jeton CSRF soumis par formulaire
 * @param string|null $token Jeton envoyé par POST
 * @return bool True si valide, False sinon
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// --------------------------------------------------------------------
// 3. AUTHENTIFICATION STAFF (Admin / Réceptionniste)
// --------------------------------------------------------------------

/**
 * Vérifie si un membre du staff est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
 * Exige une connexion staff pour accéder à une page protégée
 */
function requireLogin() {
    if (!isLoggedIn()) {
        flashMessage('danger', 'Veuillez vous connecter pour accéder à cet espace.');
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Exige un rôle spécifique (ex: 'admin')
 * @param string $requiredRole
 */
function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['utilisateur_role'] !== $requiredRole && $_SESSION['utilisateur_role'] !== 'admin') {
        flashMessage('danger', 'Accès refusé : privilèges insuffisants.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// --------------------------------------------------------------------
// 4. AUTHENTIFICATION PATIENT (Espace patient séparé)
//    IMPORTANT : utilise des clés de session DISTINCTES du staff
//    pour éviter tout conflit (patient_id ≠ utilisateur_id)
// --------------------------------------------------------------------

/**
 * Vérifie si un patient est connecté à son espace
 * @return bool
 */
function isPatientLoggedIn() {
    return isset($_SESSION['patient_id']) && !empty($_SESSION['patient_id']);
}

/**
 * Exige une connexion patient ; redirige vers la connexion patient sinon
 */
function requirePatientLogin() {
    if (!isPatientLoggedIn()) {
        flashMessage('warning', 'Veuillez vous connecter à votre espace patient pour continuer.');
        header('Location: ' . BASE_URL . '/patients/connexion.php');
        exit;
    }
}

// --------------------------------------------------------------------
// 5. GESTION DES MESSAGES FLASH (Succès, Alerte, Erreur)
// --------------------------------------------------------------------

/**
 * Enregistre un message flash pour la prochaine requête
 * @param string $type success | danger | warning | info
 * @param string $message
 */
function flashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'text' => $message];
}

/**
 * Récupère et vide les messages flash de la session
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// --------------------------------------------------------------------
// 6. MOTEUR CENTRAL DE VÉRIFICATION DES RÈGLES DE GESTION (TP SECTION 3)
// --------------------------------------------------------------------

/**
 * Vérifie toutes les contraintes de disponibilité pour la création ou modification d'un rendez-vous.
 * 
 * Règles vérifiées :
 * 1. Existence du patient.
 * 2. Disponibilité du médecin (horaires de travail, absences, réunions, congés).
 * 3. Absence de chevauchement de rendez-vous pour le médecin.
 * 4. Disponibilité de la salle (ouverture, maintenance, fermeture).
 * 5. Absence de chevauchement de rendez-vous pour la salle.
 * 6. Adéquation des équipements requis par rapport aux équipements de la salle.
 * 
 * @param PDO $pdo Connexion active
 * @param int $id_patient
 * @param int $id_medecin
 * @param int $id_salle
 * @param string $date_heure_debut Format 'Y-m-d H:i:s'
 * @param int $duree_minutes Durée en minutes (ex: 30, 45, 60)
 * @param string|null $equipement_requis Équipement spécifique requis (optionnel)
 * @param int|null $exclude_rdv_id ID du RDV à exclure lors d'une modification
 * @return array ['valide' => bool, 'erreurs' => string[], 'suggestions' => array]
 */
function verifierDisponibiliteRdv($pdo, $id_patient, $id_medecin, $id_salle, $date_heure_debut, $duree_minutes, $equipement_requis = null, $exclude_rdv_id = null) {
    $erreurs = [];
    $suggestions = [
        'autres_creneaux' => [],
        'autres_salles' => []
    ];

    // Calcul de la date et heure de fin
    $timestamp_debut = strtotime($date_heure_debut);
    if (!$timestamp_debut) {
        return ['valide' => false, 'erreurs' => ['La date et l heure fournies sont invalides.'], 'suggestions' => $suggestions];
    }
    $timestamp_fin = $timestamp_debut + ($duree_minutes * 60);
    $date_heure_fin = date('Y-m-d H:i:s', $timestamp_fin);

    $date_jour = date('Y-m-d', $timestamp_debut);
    $heure_debut = date('H:i:s', $timestamp_debut);
    $heure_fin = date('H:i:s', $timestamp_fin);

    // Correspondance du jour en français pour les ENUM MySQL
    $jours_fr = [
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'
    ];
    $num_jour = date('N', $timestamp_debut);
    $nom_jour = $jours_fr[$num_jour] ?? 'Lundi';

    // RÈGLE 1 : Vérifier l'existence et la validité du patient
    $stmt = $pdo->prepare("SELECT id, nom, prenom FROM patients WHERE id = ?");
    $stmt->execute([$id_patient]);
    $patient = $stmt->fetch();
    if (!$patient) {
        $erreurs[] = "Le patient sélectionné (ID #$id_patient) n'existe pas dans le système.";
    }

    // RÈGLE 2 : Vérifier le médecin (Actif et existant)
    $stmt = $pdo->prepare("SELECT id, nom, prenom, specialite, actif FROM medecins WHERE id = ?");
    $stmt->execute([$id_medecin]);
    $medecin = $stmt->fetch();
    if (!$medecin) {
        $erreurs[] = "Le médecin sélectionné n'existe pas.";
    } elseif (!$medecin['actif']) {
        $erreurs[] = "Le " . $medecin['nom'] . " n'est actuellement plus en service actif.";
    } else {
        // RÈGLE 2.1 : Vérifier que le médecin travaille ce jour-là et pendant toute la consultation
        $stmt = $pdo->prepare("SELECT * FROM disponibilites_medecins 
                               WHERE id_medecin = ? AND jour_semaine = ? 
                               AND heure_debut <= ? AND heure_fin >= ?");
        $stmt->execute([$id_medecin, $nom_jour, $heure_debut, $heure_fin]);
        $travail_medecin = $stmt->fetch();
        if (!$travail_medecin) {
            $erreurs[] = "Le " . $medecin['nom'] . " ne consulte pas le $nom_jour sur la plage $heure_debut - $heure_fin.";
        }

        // RÈGLE 2.2 : Vérifier les indisponibilités du médecin (Absences, Congés, Réunions)
        $stmt = $pdo->prepare("SELECT * FROM indisponibilites_medecins 
                               WHERE id_medecin = ? 
                               AND ? < date_fin AND ? > date_debut");
        $stmt->execute([$id_medecin, $date_heure_debut, $date_heure_fin]);
        $indispo = $stmt->fetch();
        if ($indispo) {
            $typeLabel = ucfirst($indispo['type']);
            $erreurs[] = "Le " . $medecin['nom'] . " est indisponible pour cause de {$typeLabel} ({$indispo['motif']}) du {$indispo['date_debut']} au {$indispo['date_fin']}.";
        }

        // RÈGLE 2.3 : Vérifier les chevauchements avec d'autres rendez-vous du médecin
        $sqlRdvMed = "SELECT r.*, p.nom AS patient_nom, p.prenom AS patient_prenom 
                      FROM rendez_vous r 
                      JOIN patients p ON r.id_patient = p.id
                      WHERE r.id_medecin = ? 
                      AND r.statut NOT IN ('annule')
                      AND ? < r.date_heure_fin AND ? > r.date_heure";
        $paramsRdvMed = [$id_medecin, $date_heure_debut, $date_heure_fin];
        if ($exclude_rdv_id) {
            $sqlRdvMed .= " AND r.id != ?";
            $paramsRdvMed[] = $exclude_rdv_id;
        }
        $stmt = $pdo->prepare($sqlRdvMed);
        $stmt->execute($paramsRdvMed);
        $conflitMedecin = $stmt->fetch();
        if ($conflitMedecin) {
            $erreurs[] = "Le " . $medecin['nom'] . " a déjà un rendez-vous avec {$conflitMedecin['patient_nom']} {$conflitMedecin['patient_prenom']} sur ce créneau (de {$conflitMedecin['date_heure']} à {$conflitMedecin['date_heure_fin']}).";
        }
    }

    // RÈGLE 3 : Vérifier la salle (Disponible et existante)
    $stmt = $pdo->prepare("SELECT id, nom, statut, equipements FROM salles WHERE id = ?");
    $stmt->execute([$id_salle]);
    $salle = $stmt->fetch();
    if (!$salle) {
        $erreurs[] = "La salle sélectionnée n'existe pas.";
    } elseif ($salle['statut'] !== 'disponible') {
        $erreurs[] = "La {$salle['nom']} est actuellement {$salle['statut']} et ne peut pas être réservée.";
    } else {
        // RÈGLE 3.1 : Vérifier les horaires d'ouverture de la salle
        $stmt = $pdo->prepare("SELECT * FROM disponibilites_salles 
                               WHERE id_salle = ? AND jour_semaine = ? 
                               AND heure_ouverture <= ? AND heure_fermeture >= ?");
        $stmt->execute([$id_salle, $nom_jour, $heure_debut, $heure_fin]);
        $ouverture_salle = $stmt->fetch();
        if (!$ouverture_salle) {
            $erreurs[] = "La {$salle['nom']} n'est pas ouverte le $nom_jour sur la plage $heure_debut - $heure_fin.";
        }

        // RÈGLE 3.2 : Vérifier les indisponibilités de la salle (Maintenance, Fermeture)
        $stmt = $pdo->prepare("SELECT * FROM indisponibilites_salles 
                               WHERE id_salle = ? 
                               AND ? < date_fin AND ? > date_debut");
        $stmt->execute([$id_salle, $date_heure_debut, $date_heure_fin]);
        $indispo_salle = $stmt->fetch();
        if ($indispo_salle) {
            $typeLabel = ucfirst($indispo_salle['type']);
            $erreurs[] = "La {$salle['nom']} est indisponible ({$typeLabel} : {$indispo_salle['motif']}) entre {$indispo_salle['date_debut']} et {$indispo_salle['date_fin']}.";
        }

        // RÈGLE 3.3 : Vérifier qu'aucun autre rendez-vous n'occupe cette salle au même moment
        $sqlRdvSalle = "SELECT r.*, m.nom AS medecin_nom 
                        FROM rendez_vous r 
                        JOIN medecins m ON r.id_medecin = m.id
                        WHERE r.id_salle = ? 
                        AND r.statut NOT IN ('annule')
                        AND ? < r.date_heure_fin AND ? > r.date_heure";
        $paramsRdvSalle = [$id_salle, $date_heure_debut, $date_heure_fin];
        if ($exclude_rdv_id) {
            $sqlRdvSalle .= " AND r.id != ?";
            $paramsRdvSalle[] = $exclude_rdv_id;
        }
        $stmt = $pdo->prepare($sqlRdvSalle);
        $stmt->execute($paramsRdvSalle);
        $conflitSalle = $stmt->fetch();
        if ($conflitSalle) {
            $erreurs[] = "La {$salle['nom']} est déjà réservée par le {$conflitSalle['medecin_nom']} sur ce créneau (de {$conflitSalle['date_heure']} à {$conflitSalle['date_heure_fin']}).";
        }

        // RÈGLE 3.4 : Vérifier l'adéquation des équipements requis
        if (!empty($equipement_requis)) {
            $equipements_salle = array_map('trim', explode(',', strtolower($salle['equipements'])));
            $equip_cherche = strtolower(trim($equipement_requis));
            $trouve = false;
            foreach ($equipements_salle as $equip) {
                if (strpos($equip, $equip_cherche) !== false || strpos($equip_cherche, $equip) !== false) {
                    $trouve = true;
                    break;
                }
            }
            if (!$trouve) {
                $erreurs[] = "La {$salle['nom']} ne dispose pas de l'équipement requis : '{$equipement_requis}'. Équipements présents : {$salle['equipements']}.";
            }
        }
    }

    // --------------------------------------------------------------------
    // PROPOSITION D'ALTERNATIVES LORS D'UN CONFLIT (TP Section 3)
    // --------------------------------------------------------------------
    if (!empty($erreurs)) {
        // 1. Proposer d'autres salles compatibles pour la même date/heure
        $sqlAutresSalles = "SELECT s.id, s.nom, s.equipements FROM salles s 
                            WHERE s.id != ? AND s.statut = 'disponible'
                            AND s.id IN (
                                SELECT id_salle FROM disponibilites_salles 
                                WHERE jour_semaine = ? AND heure_ouverture <= ? AND heure_fermeture >= ?
                            )
                            AND s.id NOT IN (
                                SELECT id_salle FROM indisponibilites_salles 
                                WHERE ? < date_fin AND ? > date_debut
                            )
                            AND s.id NOT IN (
                                SELECT id_salle FROM rendez_vous 
                                WHERE statut NOT IN ('annule') AND ? < date_heure_fin AND ? > date_heure
                            )";
        $paramsAutres = [$id_salle, $nom_jour, $heure_debut, $heure_fin, $date_heure_debut, $date_heure_fin, $date_heure_debut, $date_heure_fin];
        $stmt = $pdo->prepare($sqlAutresSalles);
        $stmt->execute($paramsAutres);
        $suggestions['autres_salles'] = $stmt->fetchAll();

        // 2. Proposer d'autres créneaux pour ce même médecin dans la même journée
        if ($medecin) {
            $creneaux_a_tester = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'];
            $creneaux_dispo = [];
            foreach ($creneaux_a_tester as $h_test) {
                $test_debut = $date_jour . ' ' . $h_test . ':00';
                $test_fin = date('Y-m-d H:i:s', strtotime($test_debut) + ($duree_minutes * 60));
                
                $chkMed = $pdo->prepare("SELECT 1 FROM rendez_vous 
                                         WHERE id_medecin = ? AND statut NOT IN ('annule')
                                         AND ? < date_heure_fin AND ? > date_heure");
                $chkMed->execute([$id_medecin, $test_debut, $test_fin]);
                if (!$chkMed->fetchColumn()) {
                    $creneaux_dispo[] = $h_test;
                }
                if (count($creneaux_dispo) >= 4) break;
            }
            $suggestions['autres_creneaux'] = $creneaux_dispo;
        }
    }

    return [
        'valide' => empty($erreurs),
        'erreurs' => $erreurs,
        'suggestions' => $suggestions,
        'date_heure_fin' => $date_heure_fin
    ];
}
